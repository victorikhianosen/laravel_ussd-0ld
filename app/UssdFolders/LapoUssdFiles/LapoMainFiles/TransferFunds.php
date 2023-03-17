<?php
namespace App\UssdFolders\LapoUssdFiles\LapoMainFiles;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

class TransferFunds extends Lapo
{

    public function transferingFunds($body)
    {
        $this->initValues($body);
        switch ($this->currentStep) {
            case 3:

                return $this->result("continue", $this->render("transfer_type"));
                
            case 4:

                $this->set_value("transfer_type", $this->lastInputed);
                return $this->getAccount($body);
                
            case 5:

                $this->set_value("debit_account", $this->lastInputed);
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5'])) {
                    return $this->cancelLastAction("You entered an invalid account option. \n" . $this->getAccount($body));
                } else {

                    switch ($this->get_value('transfer_type')) {

                        case 1:

                           return $this->transferToLapo($body);
                        case 2:

                           return $this->transferToOthers($body);
                           
                        
                    }
                    
                }

                
            default:

                return $this->switchTransferType($body);

        }

    }

    public function switchTransferType($body)
    {
        switch ($this->get_value("transfer_type")) {
            case 1:
                return $this->transferToLapo($body);
            case 2:
                return $this->transferToOthers($body);
            
        }
    }

    public function transferToLapo($body)
    {
        switch ($this->currentStep) {
            case 5:

                return $this->result("continue", $this->render("credit_account"));

            case 6:

                $beneficiary = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("credit_account", $beneficiary);
                if (strlen($beneficiary) != 10) {
                    return $this->cancelLastAction("The account number you entered is not valid.\n" . $this->render("credit_account"));
                } else {
                    return $this->result("continue", $this->render("transfer_amount"));
                }

            case 7:
                $this->set_value("amount", $this->lastInputed);
                if((int)$this->lastInputed > 20000){
                    // return $this->cancelLastAction("Limit of N20,000 exceeded. \n" . $this->render("transfer_amount"));
                    $this->set_value("token",true);
                    return $this->result("continue","Limit of N20,000 exceeded. \nPlease enter token to continue:");
                }
                
                $account_check = $this->CheckBank($body);
                if ($account_check == "Successful") {

                    return $this->ValidatAccount($body);

                }else{

                    return $this->result("continue", "Account number is not a Lapo account");

                }
            case 8:
                if($this->get_value("token") == true){
                    $this->set_value("cust_token", $this->lastInputed);
                    $token_check = $this->CheckToken($body);
                    
                    if ($token_check == "0" || $token_check == 0){
                        $account_check = $this->CheckBank($body);
                        if ($account_check == "Successful") {
        
                            return $this->ValidatAccount($body);
        
                        }else{
        
                            return $this->result("continue", "Account number is not a Lapo account");
        
                        }
                    } else{
        
                        return $this->result("end", "Token Error, please try again");
    
                    }                 
                }

                $this->set_value("pin", $this->lastInputed);
                $pin = $this->PIN($body);
                if (strlen($this->lastInputed) == 4 && $pin == "Successful") {

                    return $this->QueryTransferToLapo($body);

                }
            case 9:
                //will only come here to complete the token transaction
                $this->delete_value("token");
                $this->set_value("pin", $this->lastInputed);
                $pin = $this->PIN($body);
                if (strlen($this->lastInputed) == 4 && $pin == "Successful") {

                    return $this->QueryTransferToLapo($body);

                }
            default:

                return $this->switchTransferType($body);
        }
    }

    public function transferToOthers($body)
    {
        switch ($this->currentStep) {

            case 5:

                return $this->result("continue", $this->render("credit_account"));

            case 6:

                $beneficiary = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("credit_account", $beneficiary);
                return $this->result("continue", $this->render("transfer_amount"));

            case 7:
                $this->set_value("amount", $this->lastInputed);
                if((int)$this->lastInputed > 20000){
                   // return $this->cancelLastAction("Limit of N20,000 exceeded. \n" . $this->render("transfer_amount"));
                   $this->set_value("token",true);
                   return $this->result("continue","Limit of N20,000 exceeded. \nPlease enter token to continue:");
                }
                return $this->result("continue", $this->render("banks"));

            case 8:
                if($this->get_value("token") == true){
                    $this->set_value("cust_token", $this->lastInputed);
                    $token_check = $this->CheckToken($body);
                    
                    if ($token_check == "0" || $token_check == 0){
                        return $this->result("continue", $this->render("banks")); 
                    }else{
        
                        return $this->result("end", "Token Error, please try again");
    
                    } 
                }

                $this->set_value("banks", $this->lastInputed);
                return $this->SelectBank();
            

            case 9:
                if($this->get_value("token") == true){
                    $this->set_value("banks", $this->lastInputed);
                    return $this->SelectBank();
                }

                $this->set_value("bank_code", $this->lastInputed);
                $institutionCode = $this->getInstitutionCode($this->lastInputed);
                $this->set_value("institutionCode", $institutionCode);
                return $this->QueryName($institutionCode);

            case 10:

                if($this->get_value("token") == true){
                    $this->set_value("bank_code", $this->lastInputed);
                    $institutionCode = $this->getInstitutionCode($this->lastInputed);
                    $this->set_value("institutionCode", $institutionCode);
                    return $this->QueryName($institutionCode);
                }

                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if ($validate == "Successful") {
                    return $this->QueryTransfer($body);
                } else {
                    return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                }
            case 11:
                //only for token transactions
                if($this->get_value("token") == true){
                    $this->delete_value("token");
                    $this->set_value("pin", $this->lastInputed);
                    $validate = $this->PIN($body);
                    if ($validate == "Successful") {
                        return $this->QueryTransfer($body);
                    } else {
                        return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                    }
                }
            default:

                return $this->switchTransferType($body);
            
        }
    }

    public function CheckToken($body){
        $token = $this->get_value("cust_token");
        $params = [
            "phone"=>'234'.substr($this->posted["msisdn"], -10),
            "token"=>$token
        ];
        // var_dump($params); exit;
        $client = new GuzzleHttp\Client();
        $res = $client->request('POST', "http://10.0.0.140:8080/ords/pcash/fcubs/two-factor-authentication", ['json'=>$params]);
        $response = json_decode($res->getBody());
        // var_dump($response);
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

    public function CheckBank($body)
    {
        $credit_account = $this->get_value("credit_account");
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/account?account_number=$credit_account",
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
        } elseif ($response) {
            $request = json_decode($response);
            $description = $request->description;
            return $description;
        } else {
            return "Service temporarily unavailable\n Please try again";
        }
    }

    public function SelectBank()
    {

        $banks = $this->get_value("banks");
        if ($banks == 1) {
            return $this->result("continue", $this->render("bank_one"));
        } elseif ($banks == 2) {
            return $this->result("continue", $this->render("bank_two"));
        } elseif ($banks == 3) {
            return $this->result("continue", $this->render("bank_three"));
        } elseif ($banks == 4) {
            return $this->result("continue", $this->render("bank_four"));
        } else {
            return $this->cancelLastAction("You entered an invalid option.\n" . $this->render("banks"));
        }
    }

    public function getInstitutionCode($body)
    {
        $options = $this->get_value("bank_code");
        $banks = $this->get_value("banks");
        if ($banks == 1) {
            return $this->Bank_One($options);
        } elseif ($banks == 2) {
            return $this->Bank_Two($options);
        } elseif ($banks == 3) {
            return $this->Bank_Three($options);
        } elseif ($banks == 4) {
            return $this->Bank_Four($options);
        } else {
            return $this->cancelLastAction("You entered an invalid option.\n" . $this->render("banks"));
        }
    }

    public function Bank_One($option)
    {
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

    public function Bank_Two($option)
    {
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

    public function Bank_Three($option)
    {
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

    public function Bank_Four($option)
    {
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

    public function ValidatAccount($body)
    {
        $credit_account = $this->get_value("credit_account");
        $amount = $this->get_value("amount");
        $amount_formated = number_format($amount, 2);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/account?account_number=$credit_account",
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
            $this->result("end", "An error occured. Please try again after some time.");
        }
        $yummy = json_decode($response);

        if ($yummy->code != 0) {

            return $this->result("end", "Unable to fetch account\n" . $yummy->message);

        }

        if ($response) {
            $firstname = $yummy->custname;
            $acc = $yummy->acc;
            return $this->result("continue", "LAPO Swift Banking\nTransfer N$amount_formated TO  $acc $firstname.\nEnter 4-digit PIN to confirm:");
        }else {
            return $this->result("end","Service temporarily unavailable\n Please try again");
        }
    }

    public function QueryName($institutionCode)
    {
        $acc_num = $this->get_value("credit_account");
        $amount = $this->get_value("amount");
        $amount_formated = number_format($amount, 2);
        $curl = curl_init();

        // http://10.0.0.140:8080/ords/pcash/api/resolve-account?account_number=$acc_num&bank_code=$institutionCode

        // http://10.0.0.140:8080/ords/pcash/fcubs/name-enquiry?bank_code=000012&account_number=0036961831
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/name-enquiry?bank_code=$institutionCode&account_number=$acc_num",
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
        } elseif ($response) {
            $request = json_decode($response);
            if(isset($request->account_name) && !empty($request->account_name)){
                $account_name = $request->account_name;
                return $this->result("continue", "Transfer of NGN$amount_formated To $acc_num\n$account_name\nEnter Your 4-digit PIN to confirm");
            }else{
                return $this->end("Name enquiry failed");
            }
           
        } else {
            $request = json_decode($response);
            $description = $request->description;
            return $this->cancelLastAction("$description\n" . $this->SelectBank());
        }
    }



    public function getAccount($body)
    {
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
            $this->result("end", "An error occured. Please try again after some time.");
        }

        if ($yummy->code != 0) {

            return $this->result("end", "Unable to fetch account\n" . $yummy->message);

        }

        $count = 1;
        $arr = [];
        foreach ($yummy->items as $account) {
            array_push($arr, $count . ". " . $account->ACCOUNT_NUMBER . " ");
            $this->set_value($count, $account->ACCOUNT_NUMBER);
            $count++;
        }

        $res = implode("\n", array_values($arr));
        return $this->result("continue", "Select Account:\n$res");

    }

    public function PIN($body)
    {
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
        } elseif ($response) {
            $request = json_decode($response);
            $description = $request->description;
            return $description;
        } else {
            return "Service temporarily unavailable\n Please try again";
        }
    }

    public function QueryTransfer($body)
    {
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $pin = $this->get_value("pin");
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        $credit_account = $this->get_value("credit_account");
        $amount = $this->get_value("amount");
        $institutionCode = $this->get_value("institutionCode");

        $postdata = array(
            'phone' => $msisdn,
            "pin" => $pin,
            'debit_account' => $debit_account,
            "credit_account" => $credit_account,
            'amount' => (int)$amount,
            'bank_code' => $institutionCode,
        );

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
            CURLOPT_POSTFIELDS => json_encode($postdata),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);


        if ($err) {


            return $this->result("End", "LAPO Swift Banking\nSomething went wrong, please try again after some time.");


        } 

        if ($response) {

            $data = json_decode($response);
            if (strval($data->code) == '0') {
                if(isset($data->message)){
                    $description = $data->message;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }elseif(isset($data->description)){
                    $description = $data->description;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }else{
                    return $this->result("End", "Something went wrong\n Please try again");
                }
                
            }
            if(isset($data->message)){
                $description = $data->message;  
                return $this->result("End", "LAPO Swift Banking\n$description");
            }elseif(isset($data->description)){
                $description = $data->description;  
                return $this->result("End", "LAPO Swift Banking\n$description");
            }else{
                return $this->result("End", "Something went wrong\n Please try again");
            }
            
        } else {

            return $this->result("End", "LAPO Swift Banking\nSomething went wrong, please try again after some time.");
        }
    }

    public function QueryTransferToLapo($body)
    {
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $pin = $this->get_value("pin");
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        $credit_account = $this->get_value("credit_account");
        $amount = $this->get_value("amount");
        $curl = curl_init();

        $postdata = array(
            'phone' => $msisdn,
            "pin" => $pin,
            'debit_account' => $debit_account,
            "credit_account" => $credit_account,
            'amount' => (int)$amount,
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/transfer",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postdata),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",

            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {


            return $this->result("End", "LAPO Swift Banking\nSomething went wrong, please try again after some time.");


        } 
        
        if ($response) {

            $data = json_decode($response);
            if (strval($data->code) == '0') {
                if(isset($data->message)){
                    $description = $data->message;  
                    return $this->result("End", "LAPO Swift Banking\n$description");
                }elseif(isset($data->description)){
                    $description = $data->description;  
                    return $this->result("End", "LAPO Swift Banking\nTransfer of $amount to $credit_account $data->description");
                }else{
                    return $this->result("End", "Something went wrong\n Please try again");
                }
                
            }
            if(isset($data->message)){
                $description = $data->message;  
                return $this->result("End", "LAPO Swift Banking\n$description");
            }elseif(isset($data->description)){
                $description = $data->description;  
                return $this->result("End", "LAPO Swift Banking\n$description");
            }else{
                return $this->result("End", "Something went wrong\n Please try again");
            }
            
        } else {
            

            return $this->result("End", "LAPO Swift Banking\nSomething went wrong, please try again after some time.");
            
        }
    }

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml"));
        return $arr['pages'][$key];
    }
}
