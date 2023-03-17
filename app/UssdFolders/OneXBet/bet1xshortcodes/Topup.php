<?php
namespace App\UssdFolders\OneXBet\bet1xshortcodes;
use App\Constants\UrlConstant;
use App\Http\Controllers\Bet1x;
use Illuminate\Support\Facades\Http;
use App\Constants\ApiKey;
use Exception;
use Symfony\Component\Yaml\Yaml;
use Ramsey\Uuid\Uuid;

class Topup extends Bet1x{

    public function CustomerTopup($body){
        $this->initValues($body);
        $last_inputed = $this->lastInputed;
        $this->set_value("isShortCode", true);
        $this->set_value("1xbet_type", "topup");
        switch ($this->currentStep) {
            case 1:
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
            case 2:
                if((int)$last_inputed < 20){
                    return $this->cancelLastAction("Amount must be greater than 30. \n" . $this->render("top_up"));
                }
                // $this->set_value('topup', $last_inputed);

                $amount =  $this->get_value('amount');
                $phone = substr($this->posted["msisdn"], -10);
                $this->set_value('pin', $this->lastInputed);

                $users = $this->ussdUsers();
                if(isset($users->plan)){
                    $plan = $users->plan;
                    $current_date= date('Y-m-d H:i:s');
                    $expiry = $users->expiry_date;
                    if($plan == "One-Off (N10)"){
                        return $this->result("continue", $this->render("plans")); 
                    }elseif($plan == "Weekly Subscription (N50)"){
                        if($current_date < $expiry){
                            return $this->deposit($phone,$amount); 
                        }else{
                            return $this->result("continue", $this->render("plans")); 
                        }
                        
                    }
                }
            case 3:
                $amount =  $this->get_value('amount');
                $pin =  $this->get_value('pin');
                $phone = substr($this->posted["msisdn"], -10);
                // if($last_inputed == "1"){
                $this->updateUssdUsers($last_inputed, $pin,'topup',NULL);
                $bankList='';
                foreach($this->fetchUssdBank() as $index=>$bank){
                    $bankList='.'.$index .$bankList.$bank['bank_name'].'/n';
                }
                return $this->result('continue', $bankList); 
                // return ($this->end('Your request is being processed, you will receive an sms shortly'));

                
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

    public function callApi2(array $params, $url, array $headers = NULL)
    {
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if(!is_null($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);      
        }  
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
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

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

    public function checkUser()
    {

        $msisdn = substr($this->posted["msisdn"], -10);
        // $opts = $this->get_value("option");
        // $registration_no = $this->get_value("registration");
        try {
            $data = R::getRow('select * from monnify_users where msisdn = ? limit 1', [$msisdn]);
            // var_dump($data); exit;
            return (object)$data;
        } catch (Exception $ex) {
            return $ex->getMessage();
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

    // fetch the list of banks from monnify
    public function fetchUssdBank($listOffset = 0,$length=5)
{

      // Write the YAML string to a file
      $yaml= file_get_contents('/Users/mac/Development/ussd/ussed_laravel/storage/app/public/responseFile/api-response.yml');  
      $data=Yaml::parse($yaml);  
    
    $token= $data['responseBody']['accessToken']; 
    $headers = [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
    ];
    $apiResponse= Http::withHeaders(
        $headers
    )->get(UrlConstant::$bankListUrl);
    // Convert the API response to an array
    $data = json_decode($apiResponse, true);
    
    $filteredArr = array_filter( $data['responseBody'], function($obj) {
        return $obj["baseUssdCode"] !== null;
    });
   


        // Convert the array to a YAML string
        $yaml = Yaml::dump(array_values($filteredArr));
       
        //    21 bank list
        // Write the YAML string to a file
        file_put_contents('/Users/mac/Development/ussd/ussed_laravel/storage/app/public/responseFile/bankLIst.yml', $yaml);    
        
    return array_slice($filteredArr,$listOffset,$length);

  
}

        // get bank name and bank ussd code

     public function getBankDetails($searchBy){
        // Write the YAML string to a file
        $yaml= file_get_contents('/Users/mac/Development/ussd/ussed_laravel/storage/app/public/responseFile/bankLIst.yml');  
        $data=Yaml::parse($yaml); 
        return [$data[$searchBy]['name'],$data[$searchBy]['baseUssdCode']];


        }
}



?>