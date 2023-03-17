<?php

namespace App\UssdFolders\OneXBet\bet1xshortcodes;
use Exception;
use \RedBeanPHP\R;
use Symfony\Component\Yaml\Yaml;
use App\Http\Controllers\Bet1x;

class Account extends Bet1x{

    public function CustomerAccount($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        $this->set_value("isShortCode", true);
        $this->set_value("1xbet_type", "account");
        switch ($this->currentStep) {
            case 1:
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
            case 2:
                $phone = '234'.substr($this->posted["msisdn"], -10);
                if($last_inputed == "1"){
                    $this->set_value("plan", $this->lastInputed);
                    return $this->result("continue", $this->render("my_account"));     

                 
                }elseif($last_inputed == "2"){
                    $this->set_value("plan", $this->lastInputed);
                    return $this->result("continue", $this->render("my_account")); 

             
                }
               
                return $this->balance($last_inputed);
            case 3:
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
                    $update = $this->updateUssdUsers($plan, $last_inputed,'balance');
                    return $this->result("end", $this->render("process")); 
                }
                
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

    public function ussdUsers()
    {

        $msisdn = substr($this->posted["msisdn"], -10);

        try {
            $data = R::getRow('select * from bet1x_users where msisdn = ? limit 1', [$msisdn]);
            // var_dump($data); exit;
            return (object)$data;
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

    public function updateUssdUsers($plan_mark, $enc = NULL,$action = NULL, $betcode = NULL, $withdrawal_amount = NULL)
    {

        $msisdn = substr($this->posted["msisdn"], -10);

        try {
            $data = R::exec('update bet1x_users set userAction = ?, betcode = ?, enc = ?, plan_status = "active", withdrawal_amount = ?, cron = "active", plan_mark = ? where msisdn = ?', [$action,$betcode,$enc,$withdrawal_amount,$plan_mark,$msisdn]);

        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("./apps/config/1xbet/config.yml"));
        return $arr['pages'][$key];
    }
}

?>