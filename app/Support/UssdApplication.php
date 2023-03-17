<?php

namespace App\Support;
use App\Support\IntializeUssd;
use Illuminate\Support\Facades\Redis;
use Exception;
use \RedBeanPHP\R;
 class UssdApplication
{

    public $currentStep;
    public $inputedKey;
    public $userInputs;
    public $lastInputed;
    public $posted;
    private $msisdn;
    public $userKey;
    public $persisted;
    public $defaultApp;
    private $user = 'novaji_introserve';
    private $host = '54.36.101.235';
    private $dbpass = 'Amore123_';
    private $database = 'novaji_introserve';
    public $src;


   

    // thi sis use to temporarily save the phone number to redis
    public function set_value($key, $value)
    {
        $id = $this->posted["msisdn"] . ":$key";
        Redis::set($id, $value);
        Redis::expire($id, 60);
    }

    // used to retrieve the phone number from redis
    public function get_value($key)
    {
        $id = $this->posted["msisdn"] . ":$key";
        return Redis::get($id);
    }

    // check if stored value phone number in redis
    public function value_exists($key)
    {
        $id = $this->posted["msisdn"] . ":$key";
        return Redis::exists($id);
    }

    // delete phone number from redis
    public function delete_value($key)
    {
        $id = $this->posted["msisdn"] . ":$key";
        try {
            Redis::del($id);
        } catch (Exception $e) {
        }
    }

    // this is to replace etisalat keyword with 9mobile 
    public function parseSrc($src)
    {
        if ($src == 'etisalat') {
            return '9mobile';
        }
        return $src;
    }

    // use to continue a session
    public function continueSession()
    {
        return 'Continue';
    }

    // use to end session
    public function endSession()
    {
        return 'End';
    }

    // return switch
    public function switchSession()
    {
        return 'Switch';
    }

    // used to return ussd result based on last message and last 
    // action performed
    public function result($action, $message)
    {
        # save last screen, for recall
        $screen_id = 'screen:' . $this->currentStep;
        $this->set_value($screen_id, $message);
        return ['action' => ucfirst($action), 'message' => $message];
    }

    // redis configuration file
    public function initValues($body)
    {
        $this->posted = $body;
        $this->inputedKey = InitializeUssd::getInputKey($body['msisdn']);
        $this->msisdn = $body["msisdn"];
        $this->userInputs = InitializeUssd::getUserInputs($this->inputedKey);
        $this->lastInputed = $body['content'];
        $steps = count($this->userInputs);
        $this->currentStep = ($steps > 0) ? $steps : 1;
        $this->userKey = "user:" . $body['msisdn'];
        $this->persisted = $this->userKey . ":persisted";
    }


    public function lastInput($inputs)
    {
        if ($this->posted) {
            return $this->posted['content'];
        }
        $key = count($inputs) - 1;
        return $inputs[$key];
    }

    public function expectedInput($input, $options)
    {
        return in_array($input, $options);
    }

    public function getMsisdn()
    {
        return $this->posted['msisdn'];
    }

    public function parseNumeric($val)
    {
        if (is_numeric($val)) {
            return $val;
        }
        return 0.00;
    }

    public function numericEntered($val)
    {
        return is_numeric($val);
    }

    public function integerEntered($var)
    {
        try {
            $int = (int) $var;
            return true;
        } catch (Exception $e) {
            return false;
        }
        //return is_integer((int) $var);
    }

    public function pressed_cancel()
    {
        return $this->lastInputed == 0;
    }
    public function continue($view)
    {
        # we are going 2 steps back
        return $this->result('Continue', $view);
    }

    public function end($view)
    {
        # we are going 2 steps back
        return $this->result('End', $view);
    }

    public function back($view = 'last_screen',$step=2)
    {
        # we are going 2 steps back
        $steps = $step;
        
        
        for ($i = 1; $i <= $steps; $i++) {
            # knock off last 2 inputs, takin 2 steps backward
            Redis::rpop($this->inputedKey);
            #IntializeUssd::deleteLastInput($this->inputedKey);
        }
        
        #IntializeUssd::deleteLastInput($this->inputedKey);
        $this->userInputs = InitializeUssd::getUserInputs($this->inputedKey);
        #$this->lastInputed = $body['content'];
        $steps = count($this->userInputs);
        $this->currentStep = ($steps > 0) ? $steps : 1;
        if ($view == 'last_screen') {
            $id = (int) $this->currentStep;
            $screen_id = 'screen:' . $id;
            return $this->continue($this->get_value($screen_id));
            #return $this->cancelLastAction($this->get_value($screen_id));
        }
        return $this->continue($view);
        #return $this->cancelLastAction($view);
    }

    public function backMes($mes, $view = 'last_screen')
    {
        # we are going 2 steps back with message
        $steps = 2;
        
        
        for ($i = 1; $i <= $steps; $i++) {
            # knock off last 2 inputs, takin 2 steps backward
            Redis::rpop($this->inputedKey);
            #IntializeUssd::deleteLastInput($this->inputedKey);
        }
        
        #IntializeUssd::deleteLastInput($this->inputedKey);
        $this->userInputs = InitializeUssd::getUserInputs($this->inputedKey);
        #$this->lastInputed = $body['content'];
        $steps = count($this->userInputs);
        $this->currentStep = ($steps > 0) ? $steps : 1;
        if ($view == 'last_screen') {
            $id = (int) $this->currentStep;
            $screen_id = 'screen:' . $id;
            return $this->continue($mes."\n".$this->get_value($screen_id));
            #return $this->cancelLastAction($this->get_value($screen_id));
        }
        #return $this->cancelLastAction($view);
    }

    public function cancelLastAction($view)
    {
        InitializeUssd::deleteLastInput($this->inputedKey);
        #$this->initValues($this->body);
        return $this->continue($view);
    }

    public function doSwitch($src, $dst, $msg)
    {
        return [
            'action' => $this->switchSession(),
            'srcServiceCode' => $src,
            'destServiceCode' => $dst,
            'message' => $msg
        ];
    }

    // static function launch($src, $dst, $msg)
    // {
    //     return [
    //         'action' => $this->switchSession(),
    //         'srcServiceCode' => $src,
    //         'destServiceCode' => $dst,
    //         'message' => $msg
    //     ];
    // }

    protected function persist($key, $val)
    {
        #global $redis;

        Redis::hset($this->persisted, $key, $val);
        Redis::expire($this->persisted, TTL);
    }

    protected function getAllPersisted()
    {
        #global $redis;
        return Redis::hgetall($this->persisted);
    }

    // static function switchFlashResponse($json)
    // {
	//     //db-mysql-lon1-14843-do-user-8854546-0.b.db.ondigitalocean.com:25060
    //     // $db = [
    //     //     'host' => 'localhost', 'db' => 'novaji_introserve',
    //     //     'username' => 'root', 'password' => 'Amore123_'
    //     // ];
    //     // $db = [
    //     //     'host' => 'db-mysql-lon1-14843-do-user-8854546-0.b.db.ondigitalocean.com:25060', 'db' => 'novaji_introserve',
    //     //     'username' => 'novaji_introserve', 'password' => 'yr8hg3kygqfm9fpc'
    //     // ];
    //     $db = [
    //         'host' => '46.101.23.167', 'db' => 'novaji_introserve',
    //         'username' => 'novaji', 'password' => 'Amore123_'
    //     ];
    //     R::setup('mysql:host=' . $db['host'] . ';dbname=' . $db['db'], $db['username'], $db['password']);
    //     $msisdn = $json['msisdn'];
    //     $flash = R::getRow('SELECT * FROM novaji_etisalat_ussd_flashes WHERE dst = ? order by id desc LIMIT 1', [$msisdn]);
    //     if ($flash) {
    //         if ($flash['dst_code']) {
    //             return $flash;
    //         }
    //         return null;
    //     }
    //     return null;
    // }

    // novaji database connection
    static function connect()
    {
        $isConnected = R::testConnection();
        $host = "localhost";
        $username = "novaji_introserve";
        $password = "Comfort123_";

        // $isConnected = R::testConnection();
        // $host = "localhost";
        // $username = "novaji_introserve";
        // $password = "Zh7mWr4i0A98L1mX";
        if (!$isConnected) {
            return R::setup("mysql:host=$host;dbname=novaji_introserve;", $username, $password);
        }
    }

    // static function autoSub($body)
    // {
    //     //R::close();
    //     //$trafficApp = new TrafficApp;
    //     /* check if this was trigered fromm flash
    //      */

    //     UssdApplication::connect();
    //     $flash = R::getRow('select * from novaji_etisalat_ussd_flashes where dst = ? order by id desc limit 1', [$body['msisdn']]);
    //     $src = isset($body['src']) ? $body['src'] : 'uk';
    //     if ($src == 'etisalat') {
    //         if ($flash) {
    //             switch ($flash['service_id']) {
    //                 case 1:
    //                     // ## Traffic Info
    //                     // $trafficApp = new TrafficApp();
    //                     // $trafficApp->mobile = $body['msisdn'];

    //                     // if ($trafficApp->isMsisdnRegeistered($body['msisdn']) == null) {
    //                     //     $trafficApp->addSubscriberMobiletoDB($body['msisdn']);
    //                     //     // Initiate billing
    //                     //     $trafficApp->initiateBilling($body['msisdn']);

    //                     //     if ($trafficApp->transactionid == 200) {
    //                     //         $trafficApp->logBilling($body['msisdn'], "registered"); // Log successful billing.
    //                     //     }
    //                     //     // Send SMS message to subscriber
    //                     //     $strMsg = "Thanks for registering. Dial *371# and input your origination and destination to continue @N10/day";
    //                     //     $trafficApp->sendMessage($strMsg);
    //                     // }
    //                     break;
    //                 case 2:
    //                     // Define formelo app.

    //                     $body['content'] = '*371*15#';
    //                     $body['serviceCode'] = '371';
    //                     $body['commandID'] = '111';
    //                     $initializedUssd=new IntializeUssd();
    //                     $initializedUssd->beginSession($body);
    //                     $formelo = new Formelo();
    //                     $result = $formelo->getResponse($body);
    //                     $body['reply'] = $result['message'];
    //                     $body['action'] = $result['action'];

    //                     //						$body['reply'] = $result['action'];
    //                     //						$body['action'] = $result['message'];
    //                     break;
    //                 default:
    //                     break;
    //             }
    //         }
    //         return $body;
    //     }
    // }

    // static function startSession($body)
    // {
    //     # we chose the default session to start
    //     # similate that we started a new traffic app session
    //     R::close();
    //     //$trafficApp = new TrafficApp;
    //     $body['commandID'] = '112';
    //     // $body['reply'] = $trafficApp->display('intro');
    //     $body['serviceCode'] = '371';
    //     $body['content'] = '*371#';
    //     $body['action'] = 'Continue';
    //     return $body;
    //     //$app = new TrafficApp;
    //     // start a session
    //     // $body = $app->getResponse($body);
    //     $body['commandID'] = '112';
    //     $body['action'] = 'Continue';
    //     # return a second query
    //     return $body;
    // }

    public function send_sms_message($sender, $receiver, $message)
    {
        #$sender = "371";
        if ($this->network == "etisalat") {
            file_get_contents(
                "http://sms.novajii.com/send?"
                    . "username=oracle&"
                    . "password=oracle&"
                    . "to=" . $receiver . "&"
                    . "from=$sender&"
                    . "content=" . urlencode($message) . "&"
                    . "dlr=yes&"
                    . "dlr-url=http://portal.novajii.com/smsgw/dlr&dlr-level=3&dlr-method=GET"
            );
        }
        if ($this->network == "airtel") {
            $this->send_airtel_message($sender, $message);
        }
    }

    protected function send_9mobile_message($sender, $receiver, $message)
    {
        $this->send_sms_message($sender, $receiver, $message);
    }

    protected function send_airtel_message($sender, $message)
    {
        $data = [
            "sender" => $sender,
            "destination" => "234" . substr($this->mobile, -10),
            "message" => $message
        ];

        $headers = array('Accept' => 'application/json');
        $body = Body::Form($data);
        $response = Unirest::post("http://notification.novajii.com/web/airtel/sms", $headers, $body);
        return $response;
    }

    public function select_bank($amount, $merchant_code)
    {
        $mcash = new Mcash();
        # set merchant code, very important
        $mcash->set_merchant_code($merchant_code);
        $msisdn = $this->posted["msisdn"];
        return $mcash->prepayment($msisdn, $amount);
    }

    public function initiate_payment($merchant_code, $vals)
    {
        $mcash = new Mcash();
        $mcash->set_merchant_code($merchant_code);
        $msisdn = $this->posted["msisdn"];
        $option = $this->posted["content"];
        $bank_id = $mcash->get_bank_id($msisdn, $option);
        #return $this->result("End", "Pay With Bank: " . $option);
        # parse this in comma separated values fo processing 
        $operator = $this->posted["src"];
        return $mcash->pay_with_bank($msisdn, $bank_id, "$vals,$operator");
    }
}