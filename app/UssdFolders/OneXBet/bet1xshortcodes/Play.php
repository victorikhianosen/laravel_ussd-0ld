<?php
namespace App\UssdFolders\OneXBet\bet1xshortcodes;
use App\Http\Controllers\Bet1x;
use \RedBeanPHP\R;
use Symfony\Component\Yaml\Yaml;
use Exception;

class Play extends Bet1x{

    public function CustomerPlay($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        $this->set_value("isShortCode", true);
        $this->set_value("1xbet_type", "play");
        switch ($this->currentStep) {
            case 1:
                $lists = $this->dbSportsList();
                //Enter bet code to place bet (Example 24406#1#5)
                $count = 0;
                $return_string = "Select match: \n";
                foreach($lists as $list){
                    
                    $return_string .= ++$count.'. '.$list['matches']."\n";
                    
                }
                $return_string .= "9. Next";

                return $this->result("continue", $return_string);
            case 2:
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
            case 3:
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
            case 4:
                $this->set_value("match_amount", $this->lastInputed);
                return $this->result("continue", $this->render("match_pin"));
            case 5:
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

                        
                        
                    }
                }else{
                    return $this->cancelLastAction("You entered a wrong password. \n" . $this->render("match_pin"));
                }
            case 6:
                $pin = $this->get_value('match_pin');
                $betcode = $this->get_value("betcode");
                $phone = substr($this->posted["msisdn"], -10);
                $this->updateUssdUsers($last_inputed, $pin,'play',$betcode);
                return $this->result("end", $this->render("process")); 
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