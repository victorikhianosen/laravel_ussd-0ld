<?php

namespace App\Http\Controllers;

use App\Constants\UrlConstant;
use App\Support\InitializeUssd;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Http;
use App\Support\UssdApplication;
use Exception;
use Symfony\Component\Yaml\Yaml;
use \RedBeanPHP\R as R;

use App\UssdFolders\OneXBet\bet1xshortcodes\Account;
use App\UssdFolders\OneXBet\bet1xshortcodes\Play;
use App\UssdFolders\OneXBet\bet1xshortcodes\Topup;
use App\UssdFolders\OneXBet\bet1xshortcodes\Withdraw;
use Illuminate\Http\Client\Pool;





R::setup('mysql:host=gw.novajii.com;dbname=novaji_introserve', 'novaji_introserve', 'Comfort123_');

define('SMS_USERNAME', 'info@novajii.com');
define('SMS_PASSWORD', 'Amore123_');
define('SMS_TITLE', 'NOVAJI');
define('IYKE_BASEURL', 'http://staging-gateway.iykejordanlimited.com/');
define('monify:header','Basic TUtfVEVTVF84OU5FR01BQU5OOkJOV0NTTlpWN1ZWSzFCN05EV1dLM1k0RkZOM0g1WVk4=');
define('monify:accessToken',"");

class Bet1x extends UssdApplication
{

    public $listOffset=0;

    public $listSize;

    
    public function getResponse($body)
    {
        $this->initValues($body);
        $phone = $this->posted["msisdn"];
        if(substr($this->posted["msisdn"], 0,3) != "234"){
            $phone = '234'.substr($this->posted["msisdn"], -10);
        }

        //check users  
        // $url = "https://aux-one.com/api/partners/novaji/users?filter[phone_number]=$phone&filter[phone_code]=234";
        
        $url = "https://aux-one.com/api/ussd/users/exists";
        $payload = [
            'data' => [
                'type'       => 'exists-user',
                'attributes' => [
                    'login'    => "ussd_nigeria_1x",
                    'password' => "GO3zHcAeFySmyUKl",
                    'phone_code' => 234,
                    'phone' => substr($this->posted["msisdn"], -10),
                ],
            ],
        ];

        $response = $this->api2($url,$payload);
        $content = $this->posted['content'];
    //    $response = $this->api($url,$phone);
        // return $this->existingCustomer($body);  
        // var_dump(!empty($response), $response); exit;
        if(isset($response->data) || !empty($response)){
            //check if user exist in the database
            $check = $this->ussdUsers();
           
            if(!isset($check->msisdn)){
                $this->insertUssdUser("One-Off (N10)",10.00,"inactive","");
                // post to oracle
                $params = [
                    "msisdn"=>substr($this->posted["msisdn"], -10),
                    "plan"=>"One-Off (N10)",
                    "amount"=>10,
                    "sub_date"=>NULL,
                    "expiry_date"=>NULL
                ];
                
                $res = Http::post( "https://ore.ng/ords/onexbet/1xbet-api/users", ['json'=>$params]);
                 $result = json_decode($res->getBody());
                //  print_r($check);
            }

            $mode = $this->get_value("1xbet_type");
            if($mode){
                if ($mode == "play") {
                    $play = new Play();
                    return $play->CustomerPlay($body);
                } elseif ($mode == "account") {
                    $account = new Account();
                    return $account->CustomerAccount($body);
                } elseif ($mode == "topup") {
                    $topup = new Topup();
                    return $topup->CustomerTopup($body);
                } elseif ($mode == "withdraw") {
                    $withdraw = new Withdraw();
                    return $withdraw->CustomerWithdraw($body);
                } 
            }

            if($content == "*6069*1#"){
                // $play = new Play();
                // return $play->CustomerPlay($body);
                return $this->result("end", "Welcome");
            }elseif($content == "*6069*2#"){
                $account = new Account();
                return $account->CustomerAccount($body);
            }elseif($content == "*6069*3#"){
                $topup = new Topup();
                return $topup->CustomerTopup($body);
            }elseif($content == "*6069*4#"){
                $withdraw = new Withdraw();
                return $withdraw->CustomerWithdraw($body);
            }
            return $this->existingCustomer($body); 
             
        }else{
            return $this->newCustomer($body);  
        }



    }

    public function newCustomer($body)
    {
        //function to create new users
        $this->initValues($body);
        $last_inputed = $this->lastInputed;

        switch ($this->currentStep) {
            // case 1:
            //     return $this->result("continue", $this->render("new_user")); 
            case 1:
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                $phone = '0'.substr($this->posted["msisdn"], -10);
                $result = $this->render("create_account");
                $result = explode('07066192100', $result);
                $result = $result[0].$phone.$result[1];

                return $this->result("continue", $result);
            case 2:
                $phone = substr($this->posted["msisdn"], -10);
                $plans = $this->plans($last_inputed);
                $plan = $plans->plan;
                $amount = $plans->amount;
                $check = $this->ussdUsers();
                 var_dump($check); 
                //  exit;
                if(!isset($check->msisdn)){
                    $insert = $this->insertUssdUser($plan,$amount,"active","create");
                    // post to oracle
                    $params = [
                        "msisdn"=>substr($this->posted["msisdn"], -10),
                        "plan"=>"One-Off (N10)",
                        "amount"=>10,
                        "sub_date"=>NULL,
                        "expiry_date"=>NULL
                    ];

                    $res = Http::post("https://ore.ng/ords/onexbet/1xbet-api/users", ['json'=>$params]);
                    // $result = json_decode($res->getBody());
                    // var_dump($insert);
                }
                // exit;

                //if it is from iykejordan
                $operator = $this->posted['src'];
                if ((strtolower($operator) == "airtel") || (strtolower($operator) == "9mobile")){
                    if($last_inputed == "1"){
                        $productid = "607574506fa31200193cc4f7"; //id for one off
                        $planid = "60589676c2f16c4cb88efc05";
                        $result = $this->ik($productid,$planid);
                        if(isset($result->data)){
                            if ($result->data == "processing"){
                                return $this->result("end", $this->render("process"));
                            }
                        }
                    }else{
                        $productid = "607574506fa31200193cc4f7"; //id for sub
                        $planid = "60589676c2f16c4cb88efc05";
                        $result = $this->ik($productid,$planid);
                        if(isset($result->data)){
                            if ($result->data == "processing"){
                                return $this->result("end", $this->render("process"));
                            }
                        }
                    }
                   
                } //end iykejordan

                //mobile intelligence
                $this->updateUssdUsers($last_inputed, NULL,'create');

                //run background job
                $user = [
                    'msisdn'=>$phone,
                    'plan_mark'=>$last_inputed
                ];
                $pool = Pool::create();
                $pool->add(function () use ($user){
                    // ...  
                    return ['number'=>(string)$user['msisdn'], 'mark'=>(string)$user['plan_mark']];
                })->then(function ($output){
                // On success, `$output` is returned by the process or callable you passed to the queue.
                    
                    if($output['mark'] == '1'){
                        $oneoff = $this->oneOff($output['number']);
                        $this->removeCron();
    
                    }elseif($output['mark'] == '2'){
                        $sub = $this->sub($output['number']);
                        $this->removeCron();
                    }
        
                }); //end background


                // return $this->result("end", $this->render("process")); 
                if($plan == "One-Off (N10)"){
                    $oneoff = $this->oneOff($phone);
                     var_dump($oneoff); 
                    // exit;
                    if($oneoff->code == "SUCCESS"){
        
                        return $this->result("end", $this->render("process"));  
                    }else{
                        return $this->end('An Error occurred, please try again'); 
                    }
                     
                }elseif($plan == "Weekly Subscription (N50)"){
                    $sub = $this->sub($phone);
                    if($sub->code == "SUCCESS"){
                        return $this->result("end", $this->render("process"));  
                    }else{
                        return $this->end('An Error occurred, please try again'); 
                    }
                }

            case 3:
                //API to create new users to be used here
                
                $url = 'https://aux-one.com/api/ussd/registration/';
                $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                $login = 'ussd_nigeria_1x';
                $password = 'GO3zHcAeFySmyUKl';
                $phone = substr($this->posted["msisdn"], -10);
                $phone_code = '234';

                $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code);
                
                if($result->success == true){
                    return $this->result("end", $this->render("confirm_account").' Your ID is '.$result->description->user_id.' and your password is '.$result->description->password);
                }

                
        }
        
    }

    public function existingCustomer($body)
    {
        //function to create new users
        $this->initValues($body);
        $last_inputed = $this->lastInputed;

        switch ($this->currentStep) {
            case 1:
                return $this->result("continue", $this->render("menu")); 
            case 2:

                switch ($last_inputed) {
                    case 1:
                        if($last_inputed == "00"){
                           
                            return $this->back();
                        }
                        $this->set_value("option", 'play');
                        return $this->play($body);
                    case 2:
                        if($last_inputed > 6){
                            return $this->cancelLastAction("You entered a wrong option. \n" . $this->render("menu"));
                        }
                        if($last_inputed == "00"){
                            
                            return $this->back();
                        }
                        $this->set_value("option", 'account');
                        return $this->account($body);
                    case 3:
                        if($last_inputed > 6){
                            return $this->cancelLastAction("You entered a wrong option. \n" . $this->render("menu"));
                        }
                        if($last_inputed == "00"){
                            
                            return $this->back();
                        }
                        $this->set_value("option", 'topup');
                        return $this->topup($body);
                    case 4:
                        if($last_inputed > 6){
                            return $this->cancelLastAction("You entered a wrong option. \n" . $this->render("menu"));
                        }
                        if($last_inputed == "00"){
                            
                            return $this->back();
                        }
                        $this->set_value("option", 'withdraw');
                        return $this->withdraw($body);
                    case 5:
                        if($last_inputed == "00"){
                            
                            return $this->back();
                        }
                        $this->sms('234'.substr($this->posted["msisdn"], -10),$this->render("reset"));
                        $this->set_value("option", 'reset');
                        return $this->result("end", $this->render("reset")); 
                    
                    
                }
            default:
                switch ($this->get_value('option')){
                    case 'play':
                        return $this->play($body);
                    case 'account':
                        return $this->account($body);
                    case 'topup':
                        return $this->topup($body);
                    case 'withdraw':
                        return $this->withdraw($body);
                    case 'reset':
                        return $this->result("continue", $this->render("reset")); 
                }
                
                break;

                
        }
        
    }


    public function play($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        switch ($this->currentStep) {
            // case 2:
            //     $this->set_value('menu', $this->lastInputed);
            //     if($last_inputed == "00"){
                           
            //         return $this->back();
            //     }
            //     return $this->result("continue", $this->render("play")); 

            case 2:
              
                // switch ($last_inputed) {
                //     //press 1
                //     case 1:
                //         $this->set_value("option-play", 'matchOftheday');
                //         return $this->matchOftheday();
                //     //press 2
                //     case 2:
                //         $this->set_value("option-play", 'esports');
                //         return $this->esports();
                // }
                // return $this->end("This feature is currently unavailable");
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                $lists = $this->dbSportsList();
              //Enter bet code to place bet (Example 24406#1#5)
                $count = 0;
                $return_string = "Select match: \n";
                foreach($lists as $list){
                    
                    $return_string .= ++$count.'. '.$list['matches']."\n";
                    
                }
                $return_string .= "9. Next";

                return $this->result("continue", $return_string);
      

            case 3:
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                if ($this->lastInputed == "9") {
                    $current_page = $this->get_value('current_page');
                    if(!isset($current_page)){
                        $this->set_value('current_page', 2);
                        $lists = $this->dbSportsList(2);
                        // var_dump($lists); exit;
                        $count = 0;
                        $return_string= "Select match: \n";
                        foreach($lists as $list){
                            // var_dump($match);
                            $return_string .= ++$count.'. '.$list['matches']."\n";
                            
                        }
                        $return_string .= "9. Next";
    
                        $this->set_value("bet_code", $this->lastInputed);
                        return $this->cancelLastAction($return_string);
                    }
    
                    $next = (int)$this->get_value('current_page') + 2;
                    $lists = $this->dbSportsList($next);
                    $last_data = $this->dbSportsList(NULL,true);
    
                    $count = 0;
                    $return_string= "Select match: \n";
                    $last_key = array_key_last($lists);
                    foreach($lists as $list){
                        // var_dump($match);
                        $return_string .= ++$count.' '.$list['matches']."\n";
                        
                    }
                    $return_string .= "9. Next";
                    if($lists[$last_key] == $last_data['matches']){
                        str_replace("9. Next","",$return_string); 
                    }
    
                    $this->set_value("bet_code", $this->lastInputed);
                    $this->set_value('current_page', (int)$current_page + 2);
                    return $this->cancelLastAction($return_string);
                }

                                
                $current_page =(int)$this->get_value('current_page');
                $lists = $this->dbSportsList($current_page);
                if(!isset($current_page)){
                    $lists = $this->dbSportsList();
                }
                $count = 0;
                foreach($lists as $list){
                    $match[++$count] =  $list['matches'];      
                }
                $code = explode("Match code: ", $match[$this->lastInputed]);
                $this->set_value("matchcode", $code[1]);

                $this->set_value("bet_code", $this->lastInputed);
                return $this->result("continue", $this->render("match_options"));
            case 4:
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                $options = [
                    '1' => "1",
                    '2' => "X",
                    '3' => "2",
                    '4' => "1X",
                    '5' => "12",
                    '6' => "2X"
                ];
   
                $this->set_value("match_option", $options[$this->lastInputed]);
                return $this->result("continue", $this->render("match_amount"));
            case 5:
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                $this->set_value("match_amount", $this->lastInputed);
                return $this->result("continue", $this->render("match_pin"));
            case 6: 
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                $this->set_value("match_pin", $this->lastInputed);
                // $amount_array = explode("#",$this->get_value("bet_code"));
                $amount = $this->get_value('match_amount');
                $betcode = $this->get_value('matchcode')."#".$this->get_value('match_option')."#".$this->get_value('match_amount');
                // var_dump($betcode); exit;
                $this->set_value("betcode", $betcode);
                //API to check if users password is correct
                $url = 'https://aux-one.com/api/ussd/authorization/';
                $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                $login = 'ussd_nigeria_1x';
                $password = 'GO3zHcAeFySmyUKl';
                $phone = substr($this->posted["msisdn"], -10);
                $phone_code = '234';
                $user_pass = $last_inputed;

                $passwordConfirmation = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
                $passwordCheck = $passwordConfirmation->success;

                if($passwordCheck == true){
                    //API to check users account balance
                    $url = 'https://aux-one.com/api/ussd/balance/';
                    $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                    $login = 'ussd_nigeria_1x';
                    $password = 'GO3zHcAeFySmyUKl';
                    $phone = substr($this->posted["msisdn"], -10);
                    $phone_code = '234';
                    $user_pass = $last_inputed;

                    $balanceCheck = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
                    $balance = $balanceCheck->description->money;
                
                    if($amount >= $balance){
                       //ToDo: update password, action and betcode in db 

                        $users = $this->ussdUsers();
                        if(isset($users->plan)){
                            $plan = $users->plan;
                            $current_date= date('Y-m-d H:i:s');
                            $expiry = $users->expiry_date;
                            if($plan == "One-Off (N10)"){
                                return $this->result("continue", $this->render("plans")); 
                            }elseif($plan == "Weekly Subscription (N50)"){
                                if($current_date < $expiry){
                                    return $this->placeBet($last_inputed); 
                                }else{
                                    return $this->result("continue", $this->render("plans")); 
                                }
                                
                            }
                        }

                        
                        
                    }else{
                        return $this->cancelLastAction("Insufficient balance. \n" . $this->render("match_pin"));
                    }
                }else{
                    return $this->cancelLastAction("You entered a wrong password. \n" . $this->render("match_pin"));
                }

            case 7:
                $operator = $this->posted['src'];
                $pin = $this->get_value('match_pin');
                $betcode = $this->get_value("betcode");
                $phone = substr($this->posted["msisdn"], -10);
                $this->updateUssdUsers($last_inputed, $pin,'play',$betcode);
                if ((strtolower($operator) == "airtel") || (strtolower($operator) == "9mobile")){
                    if($last_inputed == "1"){
                        $productid = "607574506fa31200193cc4f7"; //id for one off
                        $planid = "60589676c2f16c4cb88efc05";
                        $result = $this->ik($productid,$planid);
                        if(isset($result->data)){
                            if ($result->data == "processing"){
                                return $this->result("end", $this->render("process"));
                            }
                        }
                    }else{
                        $productid = "607574506fa31200193cc4f7"; //id for sub
                        $planid = "60589676c2f16c4cb88efc05";
                        $result = $this->ik($productid,$planid);
                        if(isset($result->data)){
                            if ($result->data == "processing"){
                                return $this->result("end", $this->render("process"));
                            }
                        }
                    }
                   
                } //end iykejordan

                //run background job
                $user = [
                    'msisdn'=>$phone,
                    'plan_mark'=>$last_inputed
                ];
                $pool = Pool::create();
                $pool->add(function () use ($user){
                    // ...  
                    return ['number'=>(string)$user['msisdn'], 'mark'=>(string)$user['plan_mark']];
                })->then(function ($output){
                // On success, `$output` is returned by the process or callable you passed to the queue.
                    
                    if($output['mark'] == '1'){
                        $oneoff = $this->oneOff($output['number']);
                        $this->removeCron();
    
                    }elseif($output['mark'] == '2'){
                        $sub = $this->sub($output['number']);
                        $this->removeCron();
                    }
                                  
                }); //end background
  
                return $this->result("end", $this->render("process")); 

        }
    }

    public function dbPlay(){
        // case 2:
            // $url = 'https://aux-one.com/api/ussd/sport/';
            // $key = 'hjASWbhdkiqa3w3n32542nNASDda';
            // $login = 'ussd_nigeria_1x';
            // $password = 'GO3zHcAeFySmyUKl';
            // $phone = substr($this->posted["msisdn"], -10);
            // $phone_code = '234';
            // $sport_id = 1;


            // $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, NULL, NULL, $sport_id);
            // $this->set_value('total_page', $result->description->pages);
           
            // $matches = $result->description->events;
            // $count = 0;
            // $return_string = "Enter bet code to place bet (Example 24406#1#5): \n";
            // foreach($matches as $match){
               
            //     $return_string .= ++$count.". ".$match[1]." V ".$match[2].". Match code: ".$match[0]."\n";
                
            // }
            // $return_string .= "9. Next";
            
            // return $this->result("continue", $return_string); 
    
        // case 3:
            // if ($this->lastInputed == 9) {
                    
            //     $current_page = $this->get_value('current_page');
            //     if(!isset($current_page)){

                    
                    
            //         $this->set_value('current_page', 1);

            //         $url = 'https://aux-one.com/api/ussd/sport/';
            //         $key = 'hjASWbhdkiqa3w3n32542nNASDda';
            //         $login = 'ussd_nigeria_1x';
            //         $password = 'GO3zHcAeFySmyUKl';
            //         $phone = substr($this->posted["msisdn"], -10);
            //         $phone_code = '234';
            //         $sport_id = 1;
            //         $current_page = 1;

            //         $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, NULL, NULL, $sport_id, $current_page);
            //         $matches = $result->description->events;
            //         $count = 0;
            //         $return_string= "";
            //         foreach($matches as $match){
            //             // var_dump($match);
            //             $return_string .= ++$count.". ".$match[1]." V ".$match[2].". Match code: ".$match[0]."\n";
                        
            //         }
            //         $return_string .= "9. Next";

            //         $this->set_value("bet_code", $this->lastInputed);
            //         return $this->cancelLastAction($return_string);
            //     }
            //     $url = 'https://aux-one.com/api/ussd/sport/';
            //     $key = 'hjASWbhdkiqa3w3n32542nNASDda';
            //     $login = 'ussd_nigeria_1x';
            //     $password = 'GO3zHcAeFySmyUKl';
            //     $phone = substr($this->posted["msisdn"], -10);
            //     $phone_code = '234';
            //     $sport_id = 1;
            //     $current_page = (int) $current_page + 1;
                
            //     $total_page = $this->get_value('total_page');
                
            //     $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, NULL, NULL, $sport_id, $current_page);
            //     $matches = $result->description->events;
            //     $return_string= "";
            //     $count = 0;
            //     foreach($matches as $match){
                   
            //         $return_string .= ++$count.". ".$match[1]." V ".$match[2].". Match code: ".$match[0]."\n";
                    
            //     }

            //     if($current_page < $total_page){
            //         $return_string .= "9. Next";
            //     }

            //     $this->set_value("bet_code", $this->lastInputed);
            //     $this->set_value('current_page', $current_page + 1);
            //     return $this->cancelLastAction($return_string);
            // }




            
            
    }

    public function dbSportsList($limit = NULL, $last_data = NULL){

        try {
            $data = R::getAll('select * from bet1x_sports limit 2');
            if(!is_null($limit)){
                $data = R::getAll('select * from bet1x_sports limit ?,2', [$limit]);
            }
            if($last_data == true){
                $data = R::getRow('select * from bet1x_sports order by id DESC limit 1');
            }
            // var_dump($data); exit;
            return $data;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function placeBet($pin){
        //API to place bet
        $url = 'https://aux-one.com/api/ussd/placebet/';
        $key = 'hjASWbhdkiqa3w3n32542nNASDda';
        $login = 'ussd_nigeria_1x';
        $password = 'GO3zHcAeFySmyUKl';
        $phone = substr($this->posted["msisdn"], -10);
        $phone_code = '234';
        $user_pass = $pin;
        $betcode = $this->get_value("betcode");


        $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass, $betcode);
        if($result->success == true){
            $notification = "Bet Accepted\n".$result->description;
            return $this->result("end", $notification);
        }
    }

    public function account($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        switch ($this->currentStep) {
            case 2:
      
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                $users = $this->ussdUsers();
                if(isset($users->plan)){
                    $plan = $users->plan;
                    $current_date= date('Y-m-d H:i:s');
                    $expiry = $users->expiry_date;
                    if($plan == "One-Off (N10)"){
                        return $this->result("continue", $this->render("plans")); 
                    }elseif($plan == "Weekly Subscription (N50)"){
                        if($current_date < $expiry){
                            return $this->result("continue", $this->render("my_account")); 
                        }else{
                            return $this->result("continue", $this->render("plans")); 
                        }
                        
                    }
                }
                
                 
            case 3:
                $phone = '234'.substr($this->posted["msisdn"], -10);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                if($last_inputed == "1"){
                    $this->set_value("plan", $this->lastInputed);
                    return $this->result("continue", $this->render("my_account"));     

                 
                }elseif($last_inputed == "2"){
                    $this->set_value("plan", $this->lastInputed);
                    return $this->result("continue", $this->render("my_account")); 

             
                }
               
                return $this->balance($last_inputed);
            case 4:
                $plan = $this->get_value('plan');
                $phone = '234'.substr($this->posted["msisdn"], -10);
                // if($plan == "1"){
                    
                     //API to check if users password is correct
                    $url = 'https://aux-one.com/api/ussd/authorization/';
                    $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                    $login = 'ussd_nigeria_1x';
                    $password = 'GO3zHcAeFySmyUKl';
                    $phone = substr($this->posted["msisdn"], -10);
                    $phone_code = '234';
                    $user_pass = $last_inputed;

                    $passwordConfirmation = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
                    $passwordCheck = $passwordConfirmation->success;
                    // var_dump($passwordCheck); exit;
                    if($passwordCheck == true){
                        $operator = $this->posted['src'];
                        $update = $this->updateUssdUsers($plan, $last_inputed,'balance');

                        if ((strtolower($operator) == "airtel") || (strtolower($operator) == "9mobile")){
                            if($plan == "1"){
                                $productid = "607574506fa31200193cc4f7"; //id for one off
                                $planid = "60589676c2f16c4cb88efc05";
                                $result = $this->ik($productid,$planid);
                                if(isset($result->data)){
                                    if ($result->data == "processing"){
                                        return $this->result("end", $this->render("process"));
                                    }
                                }
                            }else{
                                $productid = "607574506fa31200193cc4f7"; //id for sub
                                $planid = "60589676c2f16c4cb88efc05";
                                $result = $this->ik($productid,$planid);
                                if(isset($result->data)){
                                    if ($result->data == "processing"){
                                        return $this->result("end", $this->render("process"));
                                    }
                                }
                            }
                           
                        } //end iykejordan

                        //run background job
                        $user = [
                            'msisdn'=>$phone,
                            'plan_mark'=>$last_inputed
                        ];
                        $pool = Pool::create();
                        $pool->add(function () use ($user){
                            // ...  
                            return ['number'=>(string)$user['msisdn'], 'mark'=>(string)$user['plan_mark']];
                        })->then(function ($output){
                        // On success, `$output` is returned by the process or callable you passed to the queue.
                            
                            if($output['mark'] == '1'){
                                $oneoff = $this->oneOff($output['number']);
                                $this->removeCron();
            
                            }elseif($output['mark'] == '2'){
                                $sub = $this->sub($output['number']);
                                $this->removeCron();
                            }
                                        
                        }); //end background

                        return $this->result("end", $this->render("process")); 

                        // $oneoff = $this->oneOff($phone);
                        // if($oneoff->code == "SUCCESS"){
                        //     return $this->result("end", $this->render("process"));  
                        // }else{
                        //     return $this->end('An Error occurred, please try again'); 
                        // }
                    }

            //     }elseif($plan == "2"){
            //          //API to check if users password is correct
            //          $url = 'https://aux-one.com/api/ussd/authorization/';
            //          $key = 'hjASWbhdkiqa3w3n32542nNASDda';
            //          $login = 'ussd_nigeria_1x';
            //          $password = 'GO3zHcAeFySmyUKl';
            //          $phone = substr($this->posted["msisdn"], -10);
            //          $phone_code = '234';
            //          $user_pass = $last_inputed;
 
            //          $passwordConfirmation = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
            //          $passwordCheck = $passwordConfirmation->success;
            //  // var_dump($passwordCheck); exit;
            //          if($passwordCheck == true){
            //              $this->updateUssdUsers($plan, $last_inputed,'balance');

            //              $sub = $this->sub($phone);
            //              // var_dump($oneoff); exit;
            //              if($sub->code == "SUCCESS"){
            //                  return $this->result("end", $this->render("process"));  
            //              }else{
            //                  return $this->end('An Error occurred, please try again'); 
            //              }
            //          }
            //     }
                  
        }
    }

    public function balance($pass){
        //API to check if users password is correct
        $url = 'https://aux-one.com/api/ussd/authorization/';
        $key = 'hjASWbhdkiqa3w3n32542nNASDda';
        $login = 'ussd_nigeria_1x';
        $password = 'GO3zHcAeFySmyUKl';
        $phone = substr($this->posted["msisdn"], -10);
        $phone_code = '234';
        $user_pass = $pass;

        $passwordConfirmation = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
        $passwordCheck = $passwordConfirmation->success;
        // var_dump($passwordCheck); exit;
        if($passwordCheck == true){

            // API to check balance
            $url = 'https://aux-one.com/api/ussd/balance/';
            $key = 'hjASWbhdkiqa3w3n32542nNASDda';
            $login = 'ussd_nigeria_1x';
            $password = 'GO3zHcAeFySmyUKl';
            $phone = substr($this->posted["msisdn"], -10);
            $phone_code = '234';
            $user_pass = $pass;

            $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);

            $balance = $result->description->money;
            $view = $this->render("balance");
            $view = explode('Main: 0.00 NGN', $view);
            $view = $view[0].'Main: '.$balance.' NGN'.$view[1];
            
            if($result->success == true){
                $sms = $this->sms('234'.substr($phone, -10),$view);
                return $this->result("end", $view);
            }
        }else{
            return $this->cancelLastAction("You entered a wrong password. \n" . $this->render("my_account"));
        }

    }

    public function topup($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        switch ($this->currentStep) {

            case 2:
              
                $this->set_value('menu', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                $phone = '234'.substr($this->posted["msisdn"], -10);
                $params = [
                    "bank_name"=> "",
                    "amount"=> NULL,
                    "phone"=> "$phone",
                    "customer_name"=> "$phone",
                    "apikey"=>"MK_PROD_YW72C4QYWN",
                    //"MK_TEST_Q38EGU9DYL",
                    "secretkey"=>"P7UWQNANAJNBQPYZB7MBLGK7AM4UULXT",
                    //"7E4UTMSCHNXDPUFK2ESNUG3HGDFRLHYY",
                    "contractcode"=>"356156549862",
                    //"7415431651"
                    "type"=>"reserve",
                    "merchant"=>"1xbet",
                ];

                $data = $this->checkUser();
                if(!isset($data->msisdn)){
                    $result = $this->initiate_monnify($params);
                }

                return $this->result("continue", $this->render("top_up"));  

            // case 2:
            //     $this->set_value('amount', $this->lastInputed);
            //     if($last_inputed == "00"){
                            
            //         return $this->back();
            //     }
            //     return $this->result("continue", $this->render("topup_pin")); 
 
            case 3:
               
                if($last_inputed == "00"){
                    return $this->back();
                }

                if((int)$last_inputed < 20){
                    return $this->cancelLastAction("Amount must be greater than 30. \n" . $this->render("top_up"));
                }
                 $this->set_value('topup', $last_inputed);

                $amount =  $this->get_value('amount');
                $phone = substr($this->posted["msisdn"], -10);
                $this->set_value('pin', $this->lastInputed);

                $users = $this->ussdUsers();
             
                //  print_r($users->plan);
                
                if(isset($users->plan)){
                    $plan = $users->plan;
                    $current_date= date('Y-m-d H:i:s');
                    $expiry = $users->expiry_date;
                    if($plan == "One-Off (N10)"){
                      
                        return $this->result("continue", $this->render("plans")); 
                    }elseif($plan == "Weekly Subscription (N50)"){
                        if($current_date < $expiry){
                            return $this->result("continue", 'tresting values'); 
                            // return $this->deposit($phone,$amount); 
                        }else{
                            return $this->result("continue", $this->render("plans")); 
                        }
                        
                    }
                }
                else{
                    return $this->result("continue", 'This phone number is registered on 1xBet');   
                }

            case 4:
               
                // $pool = Pool::create();
                // var_dump($pool); exit;
                $amount =  $this->get_value('amount');
                $pin =  $this->get_value('pin');
                $phone = substr($this->posted["msisdn"], -10);
                $operator = $this->posted['src'];
                // if($last_inputed == "1"){
                    $this->updateUssdUsers($last_inputed, $pin,'topup',NULL);

                    if ((strtolower($operator) == "airtel") || (strtolower($operator) == "9mobile")){
                        if($last_inputed == "1"){
                            $productid = "607574506fa31200193cc4f7"; //id for one off
                            $planid = "60589676c2f16c4cb88efc05";
                            $result = $this->ik($productid,$planid);
                            if(isset($result->data)){
                                if ($result->data == "processing"){
                                    return $this->result("end", $this->render("process"));
                                }
                            }
                        }else{
                            $productid = "607574506fa31200193cc4f7"; //id for sub
                            $planid = "60589676c2f16c4cb88efc05";
                            $result = $this->ik($productid,$planid);
                            if(isset($result->data)){
                                if ($result->data == "processing"){
                                    return $this->result("end", $this->render("process"));
                                }
                            }
                        }
                       
                    } 

                    $topUp = new Topup();
                    $bankList = '';
                    $count=1;
                    $this->set_value('bankCount', $count); 
                    $banks = $topUp->fetchUssdBank();
                    
                    foreach ($banks as $index => $bank) {
                        $bankList .= ($index + 1) . '. ' . $bank['name'] . "\n";
                    }
                    
                    $lastIndex = count($banks);
                    // save the total number of banks
                    $banks = $topUp->fetchUssdBank(0,null);
                      $this->set_value('bankListSize', count($banks));            
                    
                    $bankList .=$lastIndex+1 .". Next";
                    return $this->result('continue', $bankList);
                     
                case 5:
                   
                    // check if the  list has been checked before
                    $count=$this->get_value('bankCount');
                    $this->listSize=$this->get_value('bankListSize');

                    $topUp = new Topup();
                    $bankList = '';
                            
                    // When the bank number selected  is 6 or next
                    if($this->lastInputed==6 && $count>=1){
                    // display more list
                            
                            if(intVal($this->listSize/5)<=$count){
                                $banks = $topUp->fetchUssdBank($count*5);
                                foreach ($banks as $index => $bank) {
                                    $bankList .= ($index + 1) . '. ' . $bank['name'] . "\n";
                                }
                                
                                $lastIndex = count($banks);
                                
                                $bankList .= "00. Back";
                                  
                                return $this->cancelLastAction($bankList); 

                            }
                            else{
                                $banks = $topUp->fetchUssdBank($count*5);
                                $count+=1;
                                $this->set_value('bankCount', $count); 
                                foreach ($banks as $index => $bank) {
                                    $bankList .= ($index + 1) . '. ' . $bank['name'] . "\n";
                                }
                                
                                $lastIndex = count($banks);
                                $bankList .=$lastIndex+1 .". Next". "\n";
                                $bankList .= "00. Back";
                               
                                return $this->cancelLastAction($bankList); 
                        }
                    }
                    // When the bank number selected  is 00 or back
                    if($this->lastInputed==00 && $count>=1){
                    // display more list
                            
                            if($count==1){
                                $count=1;
                                $this->set_value('bankCount', $count); 
                                $banks = $topUp->fetchUssdBank();
                                
                                foreach ($banks as $index => $bank) {
                                    $bankList .= ($index + 1) . '. ' . $bank['name'] . "\n";
                                }
                                
                                $lastIndex = count($banks);
                                // save the total number of banks
                                $banks = $topUp->fetchUssdBank(0,null);
                                  $this->set_value('bankListSize', count($banks));            
                                
                                $bankList .=$lastIndex+1 .". Next";
                                return $this->cancelLastAction($bankList);

                            }
                            if($count==2){
                                $count=1;
                                $this->set_value('bankCount', $count); 
                                $banks = $topUp->fetchUssdBank();
                                
                                foreach ($banks as $index => $bank) {
                                    $bankList .= ($index + 1) . '. ' . $bank['name'] . "\n";
                                }
                                
                                $lastIndex = count($banks);
                                // save the total number of banks
                                $banks = $topUp->fetchUssdBank(0,null);
                                  $this->set_value('bankListSize', count($banks));            
                                
                                $bankList .=$lastIndex+1 .". Next";
                                return $this->cancelLastAction($bankList);

                            }
                            else if($count>2){
                                $count-=1;
                                $this->set_value('bankCount', $count); 
                                $banks = $topUp->fetchUssdBank($count*5);
                                foreach ($banks as $index => $bank) {
                                    $bankList .= ($index + 1) . '. ' . $bank['name'] . "\n";
                                }
                                
                                $lastIndex = count($banks);
                                $bankList .=$lastIndex+1 .". Next";
                                $bankList .= "00. Back";
                                  
                                return $this->cancelLastAction($bankList); 
                        }
                    }


                    else if($this->lastInputed!=00 || $this->lastInputed!=6){
                    //   get amount to top up with
                    $amount=$this->get_value('topup');
                    $bankDetails=$topUp->getBankDetails((((intval($count)-1)*5)+ intval($this->lastInputed))-1);
                    $this->set_value('bankCode',  $bankDetails[1]); 
                    //  get the bank name 
                        // $this->delete_value('bankCount');
                        // $this->delete_value('bankListSize');
                        return $this->result('continue','You will like to top up your 1xbet'. "\n" .'account with ₦'.$amount. ' using '.$bankDetails[0] .'?'.  "\n" .' 1. Yes'. "\n" . '2. No');  
                    } 

             case 6:   
                $topUp = new Topup();
                $this->delete_value('bankCount');
                $this->delete_value('bankListSize');
                if($this->lastInputed==1){
                $ussdCodeValue=$this->get_value('bankCode');
                // remove * and # from ussd string
                $newString = str_replace(array("*", "#"), "", $ussdCodeValue);    
                 // copy the YAML file to an array
                 $response=$topUp->InitiateTransactionService('100','','',$newString);
             
                 return $this->result('continue','Dial this code on your bank phone number:'.$response[0]
                 . "\n".'to top up your 1xbet account' ."\n".
                 "Or use this checkout url ".$response[1]
                );
                }   
                else if($this->lastInputed==2){
                    $this->inputedKey = InitializeUssd::getInputKey($body['msisdn']);
                    $inputKeyValue=Redis::lrange($this->inputedKey, 0, -5);
                    Redis::del($this->inputedKey);
                    /* add the first value to inputs */
                    Redis::rpush($this->inputedKey, $inputKeyValue);   
                    return $this->result("continue", $this->render("top_up")); 
                } 
        
        }
    }

    public function deposit($phone, $amount=NULL){
        $params = [
            "bank_name"=> "",
            "amount"=> "$amount",
            "phone"=> "$phone",
            "customer_name"=> "$phone",
            "apikey"=>"MK_PROD_YW72C4QYWN",
            //"MK_TEST_Q38EGU9DYL",
            "secretkey"=>"P7UWQNANAJNBQPYZB7MBLGK7AM4UULXT",
            //"7E4UTMSCHNXDPUFK2ESNUG3HGDFRLHYY",
            "contractcode"=>"356156549862",
            //"7415431651"
            "type"=>"reserve",
            "merchant"=>"1xbet",
        ];
        
        $data = $this->checkUser();
        if(isset($data->msisdn)){

            $responseString = "Kindly transfer to the bank details below to credit your wallet \n";
            $description = $responseString."Account name: Sterling Bank \n Account Number: " .$data->accountnumber;
            $sms = $this->sms('234'.substr($phone, -10),$description);
            return $this->result("end", $description);
        }else{
            $result = $this->initiate_monnify($params);

            if($result){
                
                $responseString = "Kindly transfer to the bank details below to credit your wallet \n";
                $description = $responseString."Account name: Sterling Bank \n Account Number: " .$data->accountnumber;
                $sms = $this->sms('234'.substr($phone, -10),$description);
                return $this->result("end", 'Your request is being processed, you will receive an sms shortly');
            }
        }
    }

    public function withdraw($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        switch ($this->currentStep) {
            case 2:
                
                $this->set_value('menu', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("withdraw"));
            case 3:
        
                if($last_inputed == "00"){
                    return $this->back();
                }
                
                $this->set_value('withdraw_amount', $this->lastInputed);
                //Bank pages
                // list page 1 of banks
                return $this->continue($this->list_bank_page(1));   
            case 4:
                // list page 2 of banks
                $this->set_value("bank_page", 1);
                if ($this->lastInputed == "#") {
                    // list page 2 of banks
                    $current_page = $this->get_value('current_page');
                    if(!isset($current_page)){
                        
                        $this->set_value('current_page', 2);
                        $this->set_value("bank_page", 2);
                        return $this->cancelLastAction($this->list_bank_page(2));
                    }

                    $this->set_value("bank_page", $current_page);
                    return $this->cancelLastAction($this->list_bank_page((int) $current_page + 1));
                    
                }

                $this->set_value("selected_bank", $this->lastInputed);
                
                //api to send notification as sms needed
                return $this->result("continue", $this->render("account"));
            case 5:
                $this->set_value('account', $this->lastInputed);
                if($last_inputed == "00"){
                            
                    return $this->back();
                }
                return $this->result("continue", $this->render("account_name"));
            case 6:
                $this->set_value('account_name', $this->lastInputed);
                if($last_inputed == "00"){
                            
                    return $this->back();
                }
                return $this->result("continue", $this->render("pin"));

            case 7:
                $this->set_value('withdrawal_pin', $this->lastInputed);
                $banks = [
                    "abbey-mortgage-bank"=> 1,
                    "access-bank"=> 2,
                    "access-bank-diamond"=> 3,
                    "alat-by-wema"=> 4,
                    "asosavings"=> 5,
                    "bowen-microfinance-bank"=> 6,
                    "cemcs-microfinance-bank"=> 7,
                    "citibank-nigeria"=> 8,
                    "coronation-merchant-bank"=> 9,
                    "ecobank-nigeria"=> 10,
                    "ekondo-microfinance-bank"=> 11,
                    "eyowo"=> 12,
                    "fidelity-bank"=> 13,
                    "firmus-mfb"=> 14,
                    "first-bank-of-nigeria"=> 15,
                    "first-city-monument-bank"=> 16,
                    "fsdh-merchant-bank-limited"=> 17,
                    "globus-bank"=> 18,
                    "guaranty-trust-bank"=> 19,
                    "hackman-microfinance-bank"=> 20,
                    "hasal-microfinance-bank"=> 21,
                    "heritage-bank"=> 22,
                    "ibile-mfb"=> 23,
                    "infinity-mfb"=> 24,
                    "jaiz-bank"=> 25,
                    "keystone-bank"=> 26,
                    "kuda-bank"=> 27,
                    "lbic-plc"=> 28,
                    "mayfair-mfb"=> 29,
                    "mint-mfb"=> 30,
                    "one-finance"=> 31,
                    "palmpay"=> 32,
                    "parallex-bank"=> 33,
                    "parkway-ready-cash"=> 34,
                    "paycom"=> 35,
                    "petra-microfinance-bank-plc"=> 36,
                    "polaris-bank"=> 37,
                    "providus-bank"=> 38,
                    "rand-merchant-bank"=> 39,
                    "rubies-mfb"=> 40,
                    "sparkle-microfinance-bank"=> 41,
                    "stanbic-ibtc-bank"=> 42,
                    "standard-chartered-bank"=> 43,
                    "sterling-bank"=> 44,
                    "suntrust-bank"=> 45,
                    "taj-bank"=> 46,
                    "tcf-mfb"=> 47,
                    "titan-bank"=> 48,
                    "union-bank-of-nigeria"=> 49,
                    "united-bank-for-africa"=> 50,
                    "unity-bank"=> 51,
                    "vfd"=> 52,
                    "wema-bank"=> 53,
                    "zenith-bank"=> 54
                ];
                $selected_bank = $this->get_value('selected_bank');
                $bank_name = array_search($selected_bank,$banks);
        
                //API to check if users password is correct
                $url = 'https://aux-one.com/api/ussd/authorization/';
                $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                $login = 'ussd_nigeria_1x';
                $password = 'GO3zHcAeFySmyUKl';
                $phone = substr($this->posted["msisdn"], -10);
                $phone_code = '234';
                $user_pass = $last_inputed;
        
                $passwordConfirmation = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
                $passwordCheck = $passwordConfirmation->success;
        
                if($passwordCheck == true){
                    $amount = $this->get_value('withdraw_amount');
                    $account_number = $this->get_value('account');
                    $account_name = $this->get_value('account_name');
                    $account_name = explode(' ', $account_name);
        
        
                    // API to check balance
                    $url = 'https://aux-one.com/api/ussd/balance/';
                    $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                    $login = 'ussd_nigeria_1x';
                    $password = 'GO3zHcAeFySmyUKl';
                    $phone = substr($this->posted["msisdn"], -10);
                    $phone_code = '234';
                    $user_pass = $this->lastInputed;
        
                    $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass);
        
                    $balance = $result->description->money;
        
                    if($amount <= $balance){
                        //api to withdraw
           
                        $users = $this->ussdUsers();
                        if(isset($users->plan)){
                            $plan = $users->plan;
                            $current_date= date('Y-m-d H:i:s');
                            $expiry = $users->expiry_date;
                            if($plan == "One-Off (N10)"){
                                return $this->result("continue", $this->render("plans")); 
                            }elseif($plan == "Weekly Subscription (N50)"){
                                if($current_date < $expiry){
                                    return $this->withdrawal($last_inputed,$amount,$bank_name,$account_number,$account_name); 
                                }else{
                                    return $this->result("continue", $this->render("plans")); 
                                }
                                
                            }
                        }
             
                    }else{
                        return $this->result("end", "insufficient balance");
                    }
        
                }else{
                    return $this->result("end", "Password incorrect");
                }
            case 8:
                $amount =  $this->get_value('withdraw_amount');
                $pin =  $this->get_value('withdrawal_pin');
                $phone = substr($this->posted["msisdn"], -10);
                $operator = $this->posted['src'];
                // if($last_inputed == "1"){
                    $this->updateUssdUsers($last_inputed, $pin,'withdraw',NULL,$amount);

                    if ((strtolower($operator) == "airtel") || (strtolower($operator) == "9mobile")){
                        if($last_inputed == "1"){
                            $productid = "607574506fa31200193cc4f7"; //id for one off
                            $planid = "60589676c2f16c4cb88efc05";
                            $result = $this->ik($productid,$planid);
                            if(isset($result->data)){
                                if ($result->data == "processing"){
                                    return $this->result("end", $this->render("process"));
                                }
                            }
                        }else{
                            $productid = "607574506fa31200193cc4f7"; //id for sub
                            $planid = "60589676c2f16c4cb88efc05";
                            $result = $this->ik($productid,$planid);
                            if(isset($result->data)){
                                if ($result->data == "processing"){
                                    return $this->result("end", $this->render("process"));
                                }
                            }
                        }
                       
                    } //end iykejordan

                    //run background job
                    $user = [
                        'msisdn'=>$phone,
                        'plan_mark'=>$last_inputed
                    ];
                    $pool = Pool::create();
                    $pool->add(function () use ($user){
                        // ...  
                        return ['number'=>(string)$user['msisdn'], 'mark'=>(string)$user['plan_mark']];
                    })->then(function ($output){
                    // On success, `$output` is returned by the process or callable you passed to the queue.
                        
                        if($output['mark'] == '1'){
                            $oneoff = $this->oneOff($output['number']);
                            $this->removeCron();
        
                        }elseif($output['mark'] == '2'){
                            $sub = $this->sub($output['number']);
                            $this->removeCron();
                        }
                                    
                    }); //end background

                    return $this->end('Your request is being processed, you will receive an sms shortly');
                    // $oneoff = $this->oneOff($phone);
                    // if($oneoff->code == "SUCCESS"){
                    //     return $this->end('Your request is being processed, you will receive an sms shortly'); 
                    // }else{
                    //     return $this->end('An Error occurred, please try again'); 
                    // }
                // }elseif($last_inputed == "2"){
                //     $this->updateUssdUsers($last_inputed, $pin,'withdraw',NULL,$amount);

                //     $sub = $this->sub($phone);
                //     // var_dump($oneoff); exit;
                //     if($sub->code == "SUCCESS"){
                //         return $this->end('Your request is being processed, you will receive an sms shortly'); 
                //     }else{
                //         return $this->end('An Error occurred, please try again'); 
                //     }
                // }

                
        
        }
    }

    public function withdrawal($last_inputed,$amount,$bank_name,$account_number,$account_name){
        $url = "https://aux-one.com/api/ussd/payments/withdraw/paystack";
        $payload = [
            "data" => [
                "attributes" => [
                    "login" => "ussd_nigeria_1x", 
                    "password" => "GO3zHcAeFySmyUKl", 
                    "user_password" => "$last_inputed", 
                    "phone_code" => 234, 
                    "phone_number" => substr($this->posted["msisdn"], -10), 
                    "amount" => $amount, 
                    "timestamp" => time(), 
                    "bank_name" => $bank_name, 
                    "bank_account_number" => $account_number, 
                    "customer_name" => $account_name[0], 
                    "customer_surname" => $account_name[1] 
                ] 
            ] 
        ]; 

        $result = $this->api2($url,$payload);
        // var_dump($result); exit;
        if(isset($result->data)){
            if($result->data){
                return $this->result("end", $this->render("process"));
            }
        }
    }

    public function matchOftheday(){
        
        $last_inputed = $this->lastInputed;
        switch ($this->currentStep) {
            case 3:
                $this->set_value('play', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("match_of_day"));  
            case 4:
                $this->set_value('match_of_day', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("match_menu2")); 
            case 5:
                $this->set_value('match_menu2', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("match_menu3")); 
            case 6:
                $this->set_value('match_menu3', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                //api to bill #10 airtime needed
                return $this->result("continue", $this->render("match_pin")); 

            case 7:
                $this->set_value('match_pin', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                //api to send sms notification needed
                return $this->result("end", $this->render("notification"));   

        }
    }

    public function esports(){

        $last_inputed = $this->lastInputed;
        $this->set_value('menu', $this->lastInputed);
        switch ($this->currentStep) {
            case 3:
                $this->set_value('play', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("esports"));  
            case 4:
                $this->set_value('esports', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("esports_menu1")); 
            case 5:
                $this->set_value('esports_menu1', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }
                return $this->result("continue", $this->render("esports_menu2")); 
            case 6:
                $this->set_value('esports_menu2', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

                return $this->result("continue", $this->render("esports_menu3")); 

            case 7:
                $this->set_value('esports_menu3', $this->lastInputed);
                if($last_inputed == "00"){
                           
                    return $this->back();
                }

               
                return $this->result("continue", $this->render("esports_pin"));   
            case 8:
                $this->set_value('esports_pin', $this->lastInputed);
                if($last_inputed == "00"){
                            
                    return $this->back();
                }

                //api to send sms notification needed
                return $this->result("end", $this->render("notification"));  

        }

    }

    public function callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass = NULL, $betcode = NULL, $sport_id = NULL, $page = NULL, $show_all = NULL, $is_live = NULL, $info = NULL, $match = NULL, $show_desc = NULL, $coupon = NULL, $from = NULL, $to = NULL, $amount = NULL)
    {

        $params = [
            'login' => $login,
            'pass' => $password,
            'phone' => $phone,
            'phone_code' => $phone_code,
        ];

        if(!empty($user_pass) || !is_null($user_pass) ){
            $params['user_pass'] = $user_pass;
        }
        if(!empty($betcode) || !is_null($betcode) ){
            $params['betcode'] = $betcode;
        }
        if(!empty($sport_id) || !is_null($sport_id) ){
            $params['sport_id'] = $sport_id;
        }
        if(!empty($page) || !is_null($page) ){
            $params['page'] = $page;
        }
        if(!empty($show_all) || !is_null($show_all) ){
            $params['show_all'] = $show_all;
        }
        if(!empty($is_live) || !is_null($is_live) ){
            $params['is_live'] = $is_live;
        }
        if(!empty($info) || !is_null($info) ){
            $params['info'] = $info;
        }
        if(!empty($match) || !is_null($match) ){
            $params['match'] = $match;
        }
        if(!empty($show_desc) || !is_null($show_desc) ){
            $params['show_desc'] = $show_desc;
        }
        if(!empty($coupon) || !is_null($coupon) ){
            $params['coupon'] = $coupon;
        }

        if(!empty($from) || !is_null($from) ){
            $params['from'] = $from;
        }
        if(!empty($to) || !is_null($to) ){
            $params['show_desc'] = $show_desc;
        }
        if(!empty($amount) || !is_null($amount) ){
            $params['amount'] = $amount;
        }


        ksort($params);
        $params['hash'] = base64_encode(hash_hmac('sha1', http_build_query($params), $key));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($result) {
            $response = json_decode($result);
            return $response;
        }
    }

    public function api($url,$phone)
    {
        
        $request_content = "/api/partners/novaji/users?filter[phone_number]=$phone&filter[phone_code]=234";
        $content = hash_hmac('sha256', $request_content, "W+Uz8&IdzGgVS>r>@^ts5I,wh7:K+r`*@XMy6nG23@N6qt,~PSd?nte=qQr>P!");
        // var_dump($content);
        $headers = array(
            'request-signature: '.$content,
            'accept: application/vnd.api+json'
        );
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        $err = curl_error($ch);
        if ($err){
            return $err;
        }
        curl_close($ch);
        if ($result) {
            $response = json_decode($result);
            return $response;
        }
    }

    public function api2($requestUrl, $payload){

        $requestHash = 'W+Uz8&IdzGgVS>r>@^ts5I,wh7:K+r`*@XMy6nG23@N6qt,~PSd?nte=qQr>P!';

        $payloadJson = json_encode($payload);

        $signature = hash_hmac('sha256', $payloadJson, $requestHash);

        $headers = [
            "request-signature: {$signature}",
            'Content-Type: application/vnd.api+json',
            'Accept: application/vnd.api+json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        // print_r($result);
        $this->logMi("check_user", 'headers: '.json_encode($headers)."\n\n".'request: '.$payloadJson."\n\nresponse: ".$result);
        $result = json_decode($result);
        $err = curl_error($ch);
        if ($err){
            return $err;
        }
        curl_close($ch);
        return $result;

    }

    // public function initiate_coral_pay($amount,$bankId)
    // {
    //     $msisdn = '0' . substr($this->posted["msisdn"], -10);
    //     $bank_codes = array(
    //         "zenithbank" => '1',
    //         "gtb" => '2',
    //         "uba" => '3',
    //         "stanbicbank" => '4',
    //         "sterlingbank" => '5',
    //         "unitybank" => '6',
    //         "keystonebank" => '7',
    //         "fidelitybank" => '8',
    //         "ecobank" => '9',
    //         "wemabank" => '10',
    //         "accessbank" => '11',
    //         "firstbank" => '12',
    //     );
    //     #return $this->result("end", "$msisdn:$amount");
        
    //     if (array_search($bankId, $bank_codes)) {

            
    //         $institutionCode = array_search($bankId, $bank_codes);
            
    //         $curl = curl_init();
    //         # dont hard code values, use variables
    //         #$url = "https://novajii.com/web/ussd-payment/api/generateRef?apikey=d7464c-107251-08d16e-fc5b31-761e22&bankId=$institutionCode&amount=10&channel=ussd&product=3rd_pty_insurance";
    //         # better way
            
    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => "https://novajii.com/web/ussd-payment/api/generateRef?apikey=95203b-0724ed-4a4ea3-74f582-27cfb0&bankId=$institutionCode&amount=$amount&channel=ussd&product=1xBet",
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'GET',
    //         ));
           
    //         $response = curl_exec($curl);
    //         // var_dump($response); 
    //         $err = curl_error($curl);
    //         curl_close($curl);
    //         if ($err) {
    //             return "an error occured\n please try again";
            
    //         } 
    //         if ($response) {
    //             $request = json_decode($response);
               
    //             if ($request->ResponseCode == "000") {
    //                 $description = $request->Description;
    //                 $message = $request->ResponseMessage;
    //                 $reference = $request->Reference;
    //                 $amount = $request->Amount;
    //                 $bank_transaction_id = $request->TransactionID;

    //                 $this->logCoralPay($message, $bank_transaction_id, $amount);
    //                 // var_dump($result,$message, $reference, $amount); exit;
    //                 # avoid hard coding messages. This should be in a config file
    //                 $msg = $description . " to 1xBet";
    //                 $this->sendPaymentSms($msg);
    //                 return $this->result("end", $msg);
    //             }else{
    //                 $message = $request->Description;
    //                 return $this->result("end", $message);
    //             }
    //         } else {
    //             return $this->end("Something went wrong\nPlease try again");
    //         }
    //     } else {

    //         return $this->end('Invalid option');
    //     }
    // }

    public function initiate_monnify(Array $params)
    {
        $msisdn = substr($this->posted["msisdn"], -10);
        
        $url = "http://152.228.212.181/novaji_webhooks/public/index.php/api/monnify/topup";
    
        $response = $this->callApi2($params, $url);
        // var_dump($response); exit;
        if ($response) {
            // var_dump($response, $response->status_code === 0); exit;
           
            if ($response->data->responseCode == "0") {
                // var_dump($response, $response->data->responseBody->accounts[0]->accountNumber); exit;
                $responseString = "Kindly transfer to the bank details below to credit your wallet \n";
                $description = $responseString."Account name: Sterling Bank \n Account Number: " .$response->data->responseBody->accounts[0]->accountNumber;
                $message = "Success";
                // $reference = $response->reference;
                // $amount = $response->amount;

                $bank_transaction_id = $response->data->responseBody->accountReference;

                $this->logMonnify($message, $bank_transaction_id);
                $this->logUser($response->data->responseBody->accounts[0]->accountNumber, $bank_transaction_id);
                // var_dump($result,$message, $reference, $amount); exit;
                # avoid hard coding messages. This should be in a config file
                // $msg = $description . " to 1xBet ".$response->bank_name." account";
                // $this->sendPaymentSms($msg);
                // return $this->result("end", $description);
                return true;
            }else{
                $message = "An error occured please try again.";
                return $this->result("end", $message);
            }
        } else {
            return $this->end("Something went wrong\nPlease try again");
        }

    }

    public function sendPaymentSms($msg)
    {

        $msisdn = $this->posted["msisdn"];
        $message = urlencode($msg);
        file_get_contents('https://novajii.com/ords/sms/api/sms?username=' . SMS_USERNAME . '&password=' . SMS_PASSWORD . '&message=' . $message . '&sender=' . SMS_TITLE . '&destination=' . $msisdn);
        /*
        file_get_contents("https://novajii.com/web/bulksms/all/send?network=etisalat&msg=$message&src=371&msisdn=$msisdn");
        file_get_contents("https://novajii.com/web/bulksms/all/send?network=airtel&msg=$message&src=371&msisdn=$msisdn");
        file_get_contents("https://novajii.com/web/bulksms/all/send?network=mtn&msg=$message&src=371&msisdn=$msisdn");
         */
        # use bulk SMS to send SMS alert
    }

    public function initiatePayment($key)
    {   
        if(isset($_POST['ResponseCode']) && $_POST['Message'] && $_POST['Amount'] && $_POST['TraceId'] && $_POST['PhoneNumber'] && $_POST['Refrence'] && $_POST['InstitutionCode'] && $_POST['TransactionId'] && $_POST['TraceId']){
            $ResponseCode = $_POST['ResponseCode'];
            $Message = $_POST['Message'];
            $Amount = $_POST['Amount'];
            $TraceId = $_POST['TraceId'];
            $PhoneNumber = $_POST['PhoneNumber'];
            $Refrence = $_POST['Refrence'];
            $InstitutionCode = $_POST['InstitutionCode'];
            $TransactionId = $_POST['TransactionId'];
            $TraceId = $_POST['TraceId'];

            if ($ResponseCode == 00){
                 //API to send payment request to 1xbet
                $url = 'https://aux-one.com/api/ussd/deposit/';
                $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                $login = 'ussd_nigeria_1x';
                $password = 'GO3zHcAeFySmyUKl';
                $phone = $PhoneNumber;
                $phone_code = '234';
                $user_pass = $this->lastInputed;
                $amount = $Amount;

                $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,$amount);
                if($result->success == true){
                    return $result;
                }
            }
        }
       


    }

    public function ik($productid, $planid){
        $operator = $this->posted['src'];
         
        if(strtolower($operator) == "airtel" || strtolower($operator) == "9mobile"){
            $url = IYKE_BASEURL.'subscriptions/api/v1/create';
            $params = [
                "subscriberId"=>'234'.substr($this->posted["msisdn"], -10),
                "productId"=>$productid, //"607574506fa31200193cc4f7", //use productid for one day (N10)
                "planId"=>$planid, //"60589676c2f16c4cb88efc05", //use planid for one day (N10)
                "network"=>strtoupper($operator),
                "channel"=>"ussd",
                "mode"=>"airtime"
            ];
           
            $res =Http::post($url, ['json'=>$params]);
            $result = json_decode($res->getBody()); 
            return $result;
        }
    }

    public function logMonnify($message, $reference, $amount=NULL)
    {

        $msisdn = substr($this->posted["msisdn"], -10);
        // $opts = $this->get_value("option");
        // $registration_no = $this->get_value("registration");
        $query = "INSERT INTO bet1x_transactions("
            . "mes, msisdn, reference, amount) "
            . "VALUES (?,?,?,?)";
        try {
            R::exec($query, [$message, $msisdn, $reference, $amount]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function ussdUsers()
    {

        $msisdn = substr($this->posted["msisdn"], -10);

        try {
            $data = R::getRow('select * from bet1x_users where msisdn = ? limit 1', [$msisdn]);
            
            return (object)$data;

        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateUssdUsers($plan_mark, $enc = NULL,$action = NULL, $betcode = NULL, $withdrawal_amount = NULL)
    {

        $msisdn = substr($this->posted["msisdn"], -10);

        try {
            // $data = R::exec('update bet1x_users set userAction = ?, betcode = ?, enc = ?, plan_status = "active", withdrawal_amount = ?, cron = "active", plan_mark = ? where msisdn = ?', [$action,$betcode,$enc,$withdrawal_amount,$plan_mark,$msisdn]);
            $data = R::exec('update bet1x_users set userAction = ?, betcode = ?, enc = ?, plan_status = "active", withdrawal_amount = ?, cron = NULL, plan_mark = ? where msisdn = ?', [$action,$betcode,$enc,$withdrawal_amount,$plan_mark,$msisdn]);

        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function removeCron()
    {

        $msisdn = substr($this->posted["msisdn"], -10);

        try {
            // $data = R::exec('update bet1x_users set userAction = ?, betcode = ?, enc = ?, plan_status = "active", withdrawal_amount = ?, cron = "active", plan_mark = ? where msisdn = ?', [$action,$betcode,$enc,$withdrawal_amount,$plan_mark,$msisdn]);
            $data = R::exec('update bet1x_users set cron = NULL, plan_mark = NULL where msisdn = ?', [$msisdn]);

        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function insertUssdUser($plan,$amount,$plan_status,$userAction,$betcode = NULL,$enc = NULL,$withdrawal_amount = NULL)
    {
        
        $msisdn = substr($this->posted["msisdn"], -10);
        // $opts = $this->get_value("option");
        // $registration_no = $this->get_value("registration");
        $query = "INSERT INTO bet1x_users("
        . "msisdn, plan, amount, plan_status, userAction, betcode, enc, withdrawal_amount) "
        . "VALUES (?,?,?,?,?,?,?,?)";
        try {
            R::exec($query, [$msisdn,$plan,$amount,$plan_status,$userAction,$betcode,$enc,$withdrawal_amount]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function logUser($account, $reference)
    {

        $msisdn = substr($this->posted["msisdn"], -10);
        // $opts = $this->get_value("option");
        // $registration_no = $this->get_value("registration");
        $query = "INSERT INTO monnify_users("
        . "msisdn, accountnumber, reference) "
        . "VALUES (?,?,?)";
        try {
            R::exec($query, [$msisdn,$account,$reference,]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function logMi($field, $payload)
    {

        $msisdn = "234".substr($this->posted["msisdn"], -10);
        // $opts = $this->get_value("option");
        // $registration_no = $this->get_value("registration");
        $query = "INSERT INTO mi_payload("
        . "msisdn, field, payload) "
        . "VALUES (?,?,?)";
        try {
            R::exec($query, [$msisdn,$field,$payload,]);
            return $query;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function checkUser()
    {

        $msisdn = substr($this->posted["msisdn"], -10);
        // $opts = $this->get_value("option");
        // $registration_no = $this->get_value("registration");
        try {
            $data = R::getRow('select * from monnify_users where msisdn = ? limit 1', [$msisdn]);
            // var_dump($data);
            return (object)$data;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function transfer(){
        $url = "https://testdev.coralpay.com/Csplash/api/FundTransfer";
        // $hash = Sha256($Username+$Password+$MerchantID+$BatchID+|+Key);
        
        $params = [
            "RequestHeader" => [
                "MerchantID" => "407000010000001", 
                "Username" => "novaji", 
                "Password" => "2752020420@003#6", 
                "Hash" => "1313o12owdbn23hbehb2yb42wd" 
            ], 
            "DebitAccount" => "5050104057", 
            "DebitBankCode" => "070", 
            "BatchID" => "0001234", 
            "PaymentRecords" => [
                [
                    "Beneficiary" => "Peter1", 
                    "Amount" => 2, 
                    "AccountNumber" => "2024543275", 
                    "BankCode" => "011", 
                    "Narration" => "sample string 4", 
                    "SerialNo" => "1", 
                    "TraceId" => "123" 
                ]
            ] 
        ]; 
        
        $response = $this->callApi2($params, $url);
        return $response;
    }

    public function sms($msisdn,$mes){
        $url = "https://nigeria.mob-intelligence.com/ng/ma/api/external/v1/sms/mt/3446";
        $auth = $this->aesEnc("ytynePkg5N7oBG1a","3693");
        $uuid = Uuid::uuid4();
        $params = [
            "productId"=>19425,
            "pricepointId"=>55698,
            "userIdentifier"=>"$msisdn",
            "mcc"=> "621",
            "mnc"=> "30",
            "msisdn"=> $msisdn,
            "context"=> "STATELESS",
            "largeAccount"=>"6069",
            "text"=> "$mes",
            "entryChannel"=>"WEB"
        ];

        $headers = [
            "Content-Type:application/json",
            "apikey:422fb720dd9840fd86870c684d3581b4",
            "authentication:$auth",
            "external-tx-id:$uuid"
        ];

        $response = $this->callApi2($params, $url, $headers);
        return $response;
    }

    public function oneOff($msisdn){
        $url = "https://nigeria.mob-intelligence.com/ng/ma/api/external/v1/charge/dob/3446";
        $auth = $this->aesEnc("NLQ1JDDtJBJvE0gg","3693");
        $uuid = Uuid::uuid4(); // ToDo: save this value
        $params = [
            "productId"=>19427,
            "pricepointId"=>55702,
            "mcc"=> "621",
            "mnc"=> "30",
            "msisdn"=>'234'.$msisdn,
            "context"=> "STATELESS",
            "largeAccount"=>"6069",
            "entryChannel"=>"WEB",
            "totalDesired"=> "1000"
        ];
        $headers = [
            "Content-Type:application/json",
            "apikey:6471035d78da4c5889c4c3dc6c77a78a",
            "authentication:$auth",
            "external-tx-id:$uuid"
        ];


        // var_dump($headers); 
        $response = $this->callApi2($params, $url, $headers);
        return $response;
    }

    public function sub($msisdn){
        $url = "https://nigeria.mob-intelligence.com/ng/ma/api/external/v1/subscription/optin/3446";
        $auth = $this->aesEnc("Hd5Bj4TVF4KYTI9k","3236");
        $uuid = Uuid::uuid4();
        $params = [
            "productId"=> 19428,
            "userIdentifier"=>'234'.$msisdn,
            "userIdentifierType"=>"MSISDN",
            "mcc"=> "621",
            "mnc"=> "30",
            "msisdn"=> '234'.$msisdn,
            "largeAccount"=>"6069",
            "entryChannel"=>"WEB"
        ];
        $headers = [
            "Content-Type:application/json",
            "apikey:075ba23b048a4dec9c72e62fe05381f9",
            "authentication:$auth",
            "external-tx-id:$uuid"           
        ];

        $response = $this->callApi2($params, $url, $headers);
        return $response;
    }

    public function callApi2(array $params, $url, array $headers = NULL)
    {
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if(!is_null($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);      
        }  
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $this->logMi("sent_payload", 'headers: '.json_encode($headers)."\n\n".'request: '.json_encode($params)."\n\nresponse: ".$result);
      
        $err = curl_error($ch);
        // $info  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        // var_dump($result, $err, $info); exit;
        if ($err){
            return $err;
        }
      
        curl_close($ch);
        if ($result) {
            $response = json_decode($result);
            return $response;
        }
    }

    public function aesEnc($key,$SID)
    {
        $milliseconds = substr(microtime(true) * 1000, 0, 13);//round(microtime(true) * 1000); 
        $content = "$SID"."#".$milliseconds;
        $cipher = "aes-128-ecb"; 
        // $key = "ytynePkg5N7oBG1a";
        $iv = "";
        

        if (in_array($cipher, openssl_get_cipher_methods())) {
            return openssl_encrypt($content, $cipher, $key, 0, $iv);
        }
    }

    public function list_bank_page($page)
    {

        switch ($page) {
            case 1:
                $ret = "1. ABBEY MORTGAGE BANK\n2. ACCESS BANK\n3. ACCESS BANK DIAMOND\n4. ALAT BY WEMA\n5. ASOSAVINGS\n6. BOWEN MICROFINANCE BANK\n7. CEMCS MICROFINANCE BANK\n8. CITIBANK NIGERIA\n9. CORONATION MERCHANT BANK\n#. MORE";
                break;
            case 2:
                $ret = "10. ECOBANK NIGERIA\n11. EKONDO MICROFINANCE BANK\n12. EYOWO\n13. FIDELITY BANK\n14. FIRMUS MFB\n15. FIRST BANK OF NIGERIA\n16. FIRST CITY MONUMENT BANK\n17. FSDH MERCHANT BANK LIMITED\n18. GLOBUS BANK\n#. MORE";
                break;
            case 3:
                $ret = "19. GUARANTY TRUST BANK\n20. HACKMAN MICROFINANCE BANK\n21. HASAL MICROFINANCE BANK\n22. HERITAGE BANK\n23. IBILE MFB\n24. INFINITY MFB\n25. JAIZ BANK\n26. KEYSTONE BANK\n27. KUDA BANK\n#. MORE";
                break;
            case 4:
                $ret = "28. LBIC PLC\n29. MAYFAIR MFB\n30. MINT MFB\n31. ONE FINANCE\n32. PALMPAY\n33. PARALLEX BANK\n34. PARKWAY READY CASH\n35. PAYCOM\n36. PETRA MICROFINANCE BANK PLC\n#. MORE";
                break;
            case 5:
                $ret = "37. POLARIS BANK\n38. PROVIDUS BANK\n39. RAND MERCHANT BANK\n40.  RUBIES MFB\n41. SPARKLE MICROFINANCE BANK\n42. STANBIC IBTC BANK\n43. STANDARD CHARTERED BANK\n44. STERLING BANK\n45. SUNTRUST BANK\n#. MORE";
                break;
            case 6:
                $ret = "46. TAJ BANK\n47. TCF MFB\n48. TITAN BANK\n49.  UNION BANK OF NIGERIA\n50. UNITED BANK FOR AFRICA\n51. UNITY BANK\n52. VFD\n53. WEMA BANK\n54. ZENITH BANK";
                break;
            default:
                $ret = "1. ABBEY MORTGAGE BANK\n2. ACCESS BANK\n3. ACCESS BANK DIAMOND\n4. ALAT BY WEMA\n5. ASOSAVINGS\n6. BOWEN MICROFINANCE BANK\n7. CEMCS MICROFINANCE BANK\n8. CITIBANK NIGERIA\n9. CORONATION MERCHANT BANK\n10. MORE";
                break;
        }
        return $ret;
    }

    public function list_bank_page_monnify($page)
    {

        switch ($page) {
            case 1:
                $ret = "1. Access bank\n2. Diamond bank\n3. Fidelity bank\n4. First bank\n5. First City Monument Bank Plc\n6. GTBank\n7. Heritage bank\n8. Keystone bank\n9. Rubies Micro-finance Bank\n20. MORE";
                break;
            case 2:
                $ret = "10. Sterling bank\n11. Union bank\n12. United Bank For Africa Plc\n13. Wema bank\n14. Zenith bank";
                break;

            default:
                $ret = "1. Access bank\n2. Diamond bank\n3. Fidelity bank\n4. First bank\n5. First City Monument Bank Plc\n6. GTBank\n7. Heritage bank\n8. Keystone bank\n9. Rubies Micro-finance Bank\n20. MORE";
                break;
        }
        return $ret;
    }

    public function plans($input){
        $plans = [
            '1' => 'One-Off (N10)',
            '2' => 'Weekly Subscription (N50)'
        ];
        $amount = [
            '1' => '10',
            '2' => '50'
        ];

        return (object)['plan'=>$plans[$input],'amount'=>$amount[$input]];
        // return (object)['plan'=>$plans($input),'amount'=>$amount($input)];
    }

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/OneXBet/1xbet/config.yml"));
        return $arr['pages'][$key];
    }

    public static function sendSmsMessage($message, $msisdn) {
        file_get_contents("http://sms.novajii.com/send?"
            . "username=oracle&"
            . "password=oracle&"
            . "to=" . $msisdn . "&"
            . "from=371&"
            . "content=" . urlencode($message) . "&"
            . "dlr=yes&"
            . "dlr-url=http://portal.novajii.com/smsgw/dlr&dlr-level=3&dlr-method=GET"
        );
    }

}




  