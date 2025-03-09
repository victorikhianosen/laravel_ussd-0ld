<?php

namespace App\Support;
use Exception;
use App\Support\UssdApplication;
use React\EventLoop\Loop;
use Illuminate\Support\Facades\Redis;
use React\EventLoop\LoopInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Yaml\Yaml;
use \RedBeanPHP\R;


define('TTL', 60);
define('SERVICE_CACHE_TIME', 3600);
define('SERVICES_KEY', 'USSD_SERVICES');
define('LOGFILE', 'ussd.log');
define('CONTINUE_SESSION', 'Continue');
define('END_SESSION', 'End');
define('UNKNOWN_APPLICATION', 'Unknown');
define('DEFAULT_APPS_CONFIG_FILE', '/Users/user/Documents/ussed_laravel/apps.yml');
define('ORACLE_USERNAME', 'novaji');
define('ORACLE_PASSWORD', 'Amore09092015_');
define('ORACLE_HOST', 'localhost');
define('ORACLE_PORT', 1521);
define('ORACLE_PDB', 'xepdb1');


class InitializeUssd 
{
    // variable for react php
    // public $loop = Loop();
    protected $loop;

    public function __construct()
    {
        $this->loop =new Loop();
    }
   
    public static function register_session($body, $wallet = null)
    {
      UssdApplication::connect();
        //try {
        $rate = isset($wallet) ? $wallet['rate'] : '0.00';
        $msisdn = $body['msisdn'];
        $provider = $body['src'];
        $str = $body['content'];
        //$code = $body['serviceCode'];
        // tokenize and get the first token for the shortcode
        $tokens = explode('*', str_replace('#', '', $str));
        // returns the code, e.g 6069 from the $token whose result is like this ['','6069']
        $code = $tokens[1];
        try {
            //$result = file_get_contents($url);
            //? this is suppose to save date to db
            $id = R::exec('insert into novaji_ussd_log(msisdn,network,user_string,rate,shortcode) values (?,?,?,?,?)', [
                $msisdn,
                $provider,
                $str,
                $rate,
                $code,
            ]);
            // push a copy to the ORDS where we have the customer portal
            $curl = curl_init();
            $req = [
                'network' => $provider,
                'msisdn' => $msisdn,
                'user_string' => $str,
                'shortcode' => $code,
            ];
            $fields = json_encode($req);
            //echo $fields.PHP_EOL;
            $curl = curl_init('http://localhost:5000/ords/sms/api/ussd-session');
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            $json_response = curl_exec($curl);
            //echo $json_response.PHP_EOL;
            //$response = json_decode($json_response);
            if ($json_response) {
                // not really doing anything here
            } else {
            }
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
            }
        } catch (Exception $e) {
        }
    }

    public function beginSession($body, $wallet = null)
    {
        
        // register new ussd sessions in an event loop, async to  get speed
        $this->loop->addTimer(1.0, function () use ($body) {
            //$body = json_decode(file_get_contents('php://input'), true);
            // in Java Ussd.register_session // static method
            // Ussd $obj = new Ussd();
            // $obj->register_session // object methid or class level function
            self::register_session($body);
        });

        $userKey = InitializeUssd::getUserKey($body['msisdn']);
        $inputKey = InitializeUssd::getInputKey($body['msisdn']);
        //global $redis;
        if (Redis::exists($inputKey)) {
            Redis::del($inputKey);
        }
        Redis::hmset($userKey, $body);
        Redis::del($body['msisdn']);
        /* add the first value to inputs */
        Redis::rpush($inputKey, $body['content']);
        Redis::expire($userKey, TTL);
        Redis::expire($inputKey, TTL);
        self::setPersistedServiceCode($body);
    }

    public static function getPersistedServiceCode($body)
    {
        //global $redis;
        $serviceCodeKey = $body['msisdn'].':service_code';
        //return Redis::get($serviceCodeKey);
        return $body['serviceCode'];
    }

    public static function setPersistedServiceCode($body)
    {
        //global $redis;


        // persist serviceCode in redis so it never breaks for 9mobile
        $serviceCodeKey = $body['msisdn'].':service_code';
        $list = explode('*', $body['content']);
        // the first values in array is empty, push it out
        //array_shift($list);
        //return reset($list);
        $service_code = $list[1];
        //Redis::set($serviceCodeKey, $body["serviceCode"]);
        Redis::set($serviceCodeKey, $service_code);
        Redis::expire($serviceCodeKey, TTL);
    }

    public static function saveInput($body)
    {
        //global $redis;
        $inputKey = InitializeUssd::getInputKey($body['msisdn']);
        Redis::rpush($inputKey, $body['content']);
    }

    public static function deleteLastInput($key)
    {
        //global $redis;
        Redis::rpop($key);
    }

    public static function getCommand($id)
    {
        $commands = [
            '111' => 'BEGIN',
            '112' => 'CONTINUE',
            '119' => 'SWITCH_BEGIN',
        ];

        return $commands[$id];
    }

    // this is use to create the key to store user msisdn in redis
    // e.g user:08151535983
    public static function getUserKey($msisdn)
    {
        return "user:{$msisdn}";
    }

    // this is use to create the key to store user input in redis
    // e.g user:08151535983
    public static function getInputKey($msisdn)
    {
        return "user:{$msisdn}:inputs";
    }

    public static function getUserInputs($key)
    {
        //global $redis;
      

        return Redis::lrange($key, 0, -1);
    }

    public static function getServiceCode($str)
    {
        //die($str);
        $ret = str_replace(['*', '#'], ['/', ''], $str);

        $str1 = explode('/', $ret);
        // Remove the first blank array element
        array_shift($str1);

        if (!empty($str1[0]) && !empty($str1[1])) {
            return '*'.$str1[0].'*'.$str1[1].'#';
            // Check if key exists else redirect to primary USSD. Code here.
        }

        return $str;
        //return "*" . $str1[0] . "#";
    }

    public static function getHandler($key)
    {
        $apps = InitializeUssd::appsfromConfig();
        // static matching, we shuld also consider patterns

        if (!empty($key)) {
           
            substr($key, 1);
            $ret = str_replace(['*', '#'], ['/', ''], $key);
            $str = explode('/', $ret);
            // Remove the first blank array element
            array_shift($str);
            
            if (!empty($str[0]) && !empty($str[1])) {
                $key = '*'.$str[0].'*'.$str[1].'#';
            // Check if key exists else redirect to primary USSD. Code here.
            } else {
                $key = '*'.$str[0].'#';
            }

            if (array_key_exists($key, $apps)) {
                return $apps[$key];
            } else {
                $key = '*'.$str[0].'#';
                if (array_key_exists($key, $apps)) {
                    return $apps[$key];
                }
            }
        }

        return UNKNOWN_APPLICATION;
    }

    public static function getHandler2($key)
    {
        $apps = InitializeUssd::appsfromConfig();
        // static matching, we shuld also consider patterns

        if (array_key_exists($key, $apps)) {
            return $apps[$key];
        }

        return UNKNOWN_APPLICATION;

        //return (array_key_exists($key, $apps)) ? $apps[$key] : 'Unknown';
    }

    public static function appsfromCache()
    {
        try {
          
            if (Redis::exists(SERVICES_KEY)) {
                // clear cache
                $data = unserialize(Redis::get(SERVICES_KEY));

                return $data;
            }
        } catch (Exception $e) {
        }
    }

    public static function appsfromConfig($file_name = 'apps.yml')
    {
        
        // first from redis cache
        try {
            $apps = self::appsfromCache();
           
            if ($apps) {
               
                return $apps;
            }
        } catch (Exception $e) {
          
            // from db
            try {
                
                return self::appsfromDb();
            } catch (Exception $e) {
                
                // from file
                return Yaml::parse(file_get_contents(DEFAULT_APPS_CONFIG_FILE));
            }
        }
    
        // all else fails
     
        return Yaml::parse(file_get_contents(DEFAULT_APPS_CONFIG_FILE));
        //return Yaml::parse(file_get_contents('apps.yml'));
    }

    public static function appsfromDb()
    {
        
        try {
            $db = DB::connection('oracle');
            $services = $db->select("select service_code,resource_class from ussd_services where status = 'active'");
            $values = [];
            foreach ($services as $e) {
                $values[$e['SERVICE_CODE']] = $e['RESOURCE_CLASS'];
            }
            // save to Redis
            
            $key = SERVICES_KEY;
            if (Redis::exists($key)) {
                // clear cache
                Redis::del($key);
            }
            // update cache
            Redis::set($key, serialize($values));
            // 3 mins in cache and the reload
            Redis::expire($key, SERVICE_CACHE_TIME);
            // dumo to yaml file

            $yaml = Yaml::dump($values);
            file_put_contents(DEFAULT_APPS_CONFIG_FILE, $yaml);

            return $values;
        } catch (Exception $e) {
        }
    }

    public static function logToFile($body)
    {
        $log_message = date('Ymdhis').
            ": MSISDN [{$body['msisdn']}] SERVICE_CODE [{$body['serviceCode']}]  CONTENT [{$body['content']}] ".PHP_EOL;
        file_put_contents(LOGFILE, $log_message, FILE_APPEND | LOCK_EX);
    }
}



