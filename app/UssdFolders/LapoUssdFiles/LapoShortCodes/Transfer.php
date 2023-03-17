<?php
namespace App\UssdFolders\LapoUssdFiles\LapoShortCodes;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Http;


class Transfer extends Lapo{

    public function TransferSteps($body,$content_three,$content_four){
        $this->initValues($body);
        $this->set_value("isShortCode", true);
        $this->set_value("lapo_type", "transfer");
        switch ($this->currentStep) {
            case 1:
                if($this->get_value("shortcode_token") == true){
                    return $this->result("continue","Limit of N20,000 exceeded. \nPlease enter token to continue:");
                }
                return $this->result("continue", $this->render("confirmation"));
            case 2:
               
                if($this->get_value("shortcode_token") == true){
                    $this->set_value("cust_token", $this->lastInputed);
                    $token_check = $this->CheckToken($body);
                    
                    if ($token_check == "0" || $token_check == 0){
                        return $this->result("continue", $this->render("confirmation"));
                    }else{
                        return $this->end("Token Error, please try again.");
                    }
                }
                $charge = $this->applySessionCharge();
                if ($charge->code != 0) {
                    return $this->result('end', $charge->description);
                }

                return $this->getAccount($body);
            case 3:
                if($this->get_value("shortcode_token") == true){
                    $charge = $this->applySessionCharge();
                    if ($charge->code != 0) {
                        return $this->result('end', $charge->description);
                    }
                    return $this->getAccount($body);
                }
                $this->set_value("debit_account", $this->lastInputed);
                $account_check = $this->CheckBank($body,$content_three,$content_four);
                if($account_check == "Successful") {
                     return $this->ValidatAccount($body,$content_three,$content_four);
                 }else{
                     return $this->result("continue", $this->render("banks"));
                 }
            case 4: 
                if($this->get_value("shortcode_token") == true){
                    $this->set_value("debit_account", $this->lastInputed);
                    $account_check = $this->CheckBank($body,$content_three,$content_four);
                    if($account_check == "Successful") {
                         return $this->ValidatAccount($body,$content_three,$content_four);
                     }else{
                         return $this->result("continue", $this->render("banks"));
                     }
                }
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if(strlen($this->lastInputed) == 4 && $validate == "Successful"){
                        return $this->QueryTransferToLapo($body,$content_three,$content_four);
                }else{
                    $this->set_value("banks", $this->lastInputed);
                    return $this->SelectBank();
                }    
            case 5: 
                if($this->get_value("shortcode_token") == true){
                    $this->set_value("pin", $this->lastInputed);
                    $validate = $this->PIN($body);
                    if(strlen($this->lastInputed) == 4 && $validate == "Successful"){
                            return $this->QueryTransferToLapo($body,$content_three,$content_four);
                    }else{
                        $this->set_value("banks", $this->lastInputed);
                        return $this->SelectBank();
                    } 
                }
                $this->set_value("bank_code", $this->lastInputed);
                $institutionCode = $this->getInstitutionCode($this->lastInputed);
                $this->set_value("institutionCode", $institutionCode);
                return $this->QueryName($institutionCode,$content_three,$content_four);
            case 6:
                if($this->get_value("shortcode_token") == true){
                    $this->set_value("bank_code", $this->lastInputed);
                    $institutionCode = $this->getInstitutionCode($this->lastInputed);
                    $this->set_value("institutionCode", $institutionCode);
                    return $this->QueryName($institutionCode,$content_three,$content_four);
                }
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if($validate == "Successful"){
                    return $this->QueryTransfer($body,$content_three,$content_four);
                }else{
                    return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                } 
            case 7:
                //only through token
                if($this->get_value("shortcode_token") == true){
                    $this->delete_value("shortcode_token");
                    $this->set_value("pin", $this->lastInputed);
                    $validate = $this->PIN($body);
                    if($validate == "Successful"){
                        return $this->QueryTransfer($body,$content_three,$content_four);
                    }else{
                        return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                    }  
                }
        }
    }

    public function CheckToken($body){
        $token = $this->get_value("cust_token");
        $params = [
            "phone"=>'234'.substr($this->posted["msisdn"], -10),
            "token"=>$token
        ];
        
        $res = 
         Http::withHeaders([
           "key" => "Content-Type",
            "value" => "application/json"
        ])->post("http://10.0.0.140:8080/ords/pcash/fcubs/two-factor-authentication", ['json'=>$params]);
       
        $response = json_decode($res->body());
        if ($response) {
            if($response->code == 0 || $response->code == '0'){
                return $response->code;
            }else{
                return 'Failed';
            }

        } else {
            return 'Failed';
        }
    }

    public function CheckBank($body,$content_three,$content_four)
    {
        // $content_four = $this->get_value("content_four");
        $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/account?account_number=$content_four",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return "an error occured\n please try again";
            }elseif($response) {
                $request = json_decode($response);
                    $description = $request->description;
                    return $description;
            }else{
                return "Service temporarily unavailable\n Please try again";
            }
    }


    public function SelectBank(){

        $banks = $this->get_value("banks");
        if($banks == 1){
            return $this->result("continue", $this->render("bank_one"));
        }elseif($banks == 2){
            return $this->result("continue", $this->render("bank_two"));
        }elseif($banks == 3){
            return $this->result("continue", $this->render("bank_three"));
        }elseif($banks == 4){
            return $this->result("continue", $this->render("bank_four"));
        }else{
            return $this->cancelLastAction("You entered an invalid option.\n" . $this->render("banks"));
        }
    }

    public function getInstitutionCode($body)
    {
        $options = $this->get_value("bank_code");
        $banks = $this->get_value("banks");
        if($banks == 1){
            return $this->Bank_One($options);
        }elseif($banks == 2){
            return  $this->Bank_Two($options);
        }elseif($banks == 3){
            return  $this->Bank_Three($options);
        }elseif($banks == 4){
            return  $this->Bank_Four($options);
        }else{
            return $this->cancelLastAction("You entered an invalid option.\n" . $this->render("banks"));
        }
    }

    
    public function Bank_One($option) { 
        $bank_codes =
        array(
            "000014" => '1',
            "000009" => '2',
            "000005" => '3',
            "000010" => '4',
            "000019" => '5',
            "000007" => '6',
            "000016" => '7',
            "000003" => '8',
        );
        if (array_search($option, $bank_codes)) {
            return array_search($option, $bank_codes);
        } else {
            return 'false';
        }
    }

    public function Bank_Two($option){
        $bank_codes =
        array(
            "000013" => '1',
            "000020" => '2',
            "000006" => '3',
            "000002" => '4',
            "090171" => '5',
            "090274" => '6',
            "000023" => '7',
            "000008" => '8',
            "000030" => '9'
        );
        if (array_search($option, $bank_codes)) {
            return array_search($option, $bank_codes);
        } else {
            return 'false';
        }
    }

    public function Bank_Three($option){
        $bank_codes =
        array(
            "090006" => '1',
            "000012" => '2',
            "000021" => '3',
            "000001" => '4',
            "000022" => '5',
            "000018" => '6',
            "000004" => '7',
            "000011" => '8',

        );
        if (array_search($option, $bank_codes)) {
            return array_search($option, $bank_codes);
        } else {
            return 'false';
        }
    }

    public function Bank_Four($option){
        $bank_codes =
        array(
            "000017" => '1',
            "000015" => '2',   
        );
        if (array_search($option, $bank_codes)) {
            return array_search($option, $bank_codes);
        } else {
            return 'false';
        }
    }

    public function QueryName($institutionCode,$content_three,$content_four)
    {
        // $content_four = $this->get_value("content_four");
        // $content_three = $this->get_value("content_three");
        $amount_formated = number_format($content_three, 2);
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/name-enquiry?bank_code=$institutionCode&account_number=$content_four",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return 'an error occured\n please try again';
            }elseif($response) {
                $request = json_decode($response);
                    if(isset($request->account_name) && !empty($request->account_name)){
                        $account_name = $request->account_name;
                        return $this->result("continue","Transfer of NGN$amount_formated To $content_four\n$account_name\nEnter Your 4-digit PIN to confirm");
                    }else{
                        return $this->end("Name enquiry failed");
                    }
                    
            }else{
                $request = json_decode($response);
                $description = $request->description;
                return $this->cancelLastAction("$description\n" . $this->SelectBank());
            } 
    }


    public function ValidatAccount($body,$content_three,$content_four) {
        $credit_account = $this->get_value("credit_account");
        // $content_four = $this->get_value("content_four");
        // $content_three = $this->get_value("content_three");
        $amount_formated = number_format($content_three, 2);
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/account?account_number=$content_four",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
        ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $yummy = json_decode($response);

            if ($err) {
                return "An error occured\n Please try again";
            }
    
            if ($yummy->code != 0) {
                
                return  $this->result("end", "Unable to validate account\n".$yummy->message);
    
            }
            if($response){
              
              $firstname = $yummy->custname;
              $acc = $yummy->acc;
              return $this->result("continue", "LAPO Swift Banking\nTransfer N$amount_formated TO $acc $firstname.\nEnter 4-digit PIN to confirm:");
            }else {
                return $this->result("end","Service temporarily unavailable\n Please try again");
            }
    }

    public function PIN($body){
        $pin = $this->get_value("pin");
        $msisdn = '0' . substr($this->posted["msisdn"], -10);

        $curl = curl_init();

       curl_setopt_array($curl, array(
       CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/auth?phone=$msisdn&pin=$pin",
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => "",
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => "GET",
       ));
       $response = curl_exec($curl);
       $err = curl_error($curl);
       curl_close($curl);
       if ($err) {
           return "an error occured\n please try again";
       }elseif($response) {
           $request = json_decode($response);
               $description = $request->description;
               return $description;
       }else{
           return "Service temporarily unavailable\n Please try again";
       }
}


    public function getAccount($body){
        $curl = curl_init();
        $msisdn = '0' . substr($this->posted["msisdn"], -10);

        curl_setopt_array($curl, array(
        CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/accounts-by-phone?phone=$msisdn",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $yummy = json_decode($response);

        if ($err) {
            return "An error occured\n Please try again";
        }

        if ($yummy->code != 0) {
            
            return  $this->result("end", "Unable to fetch account\n".$yummy->message);

        }


        $count = 1;
        $arr = [];
        foreach($yummy->items as $account){
               array_push($arr, $count.". ".$account->ACCOUNT_NUMBER." ");
               $this->set_value($count, $account->ACCOUNT_NUMBER);
                $count++;
        }
        return  $this->result("continue", "Select Account:\n". implode("\n",array_values($arr)));
    
    }

    public function QueryTransfer($body,$content_three,$content_four){
       
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $pin = $this->get_value("pin");
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        // $content_four = $this->get_value("content_four");
        // $content_three = $this->get_value("content_three");
        $institutionCode = $this->get_value("institutionCode");
        // echo "{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"debit_account\":\"$debit_account\",\"credit_account\":\"$content_four\",\"amount\":\"$content_three\",\"bank_code\":\"999$institutionCode\"}"; exit;
        // var_dump("{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"debit_account\":\"$debit_account\",\"credit_account\":\"$content_four\",\"amount\":\"$content_three\",\"bank_code\":\"999$institutionCode\"}"); exit;
        $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/transfer-other-bank",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"debit_account\":\"$debit_account\",\"credit_account\":\"$content_four\",\"amount\":\"$content_three\",\"bank_code\":\"$institutionCode\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return $this->result("End", "an error occured\n please try again");
            }elseif($response) {
                $request = json_decode($response);
                if(isset($request->message)){
                    $description = $request->message;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }elseif(isset($request->description)){
                    $description = $request->description;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }else{
                    return $this->result("End", "Something went wrong\n Please try again");
                }

            }else{
                return $this->result("End", "Something went wrong\n Please try again");
        }
    }

    public function QueryTransferToLapo($body,$content_three,$content_four)
    {
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $pin = $this->get_value("pin");
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        // $content_four = $this->get_value("content_four");
        // $content_three = $this->get_value("content_three");
        // var_dump("{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"debit_account\":\"$debit_account\",\"credit_account\":\"$content_four\",\"amount\":\"$content_three\"}"); exit;
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/transfer",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>"{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"debit_account\":\"$debit_account\",\"credit_account\":\"$content_four\",\"amount\":\"$content_three\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return "an error occured\n please try again";
            }elseif($response) {
                $request = json_decode($response);
                if(isset($request->message)){
                    $description = $request->message;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }elseif(isset($request->description)){
                    $description = $request->description;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }else{
                    return $this->result("End", "Something went wrong\n Please try again");
                }

            }else{
                return "Something went wrong\n Please try again";
        }
    }
    public function render($key){
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml"));
        return $arr['pages'][$key];
    }
}