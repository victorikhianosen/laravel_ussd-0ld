<?php
namespace App\UssdFolders\LapoUssdFiles\LapoShortCodes;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Http;


class AirtimeOthers extends Lapo{

    public function AirtimeForOthers($body,$content_three,$content_four){
        $this->initValues($body);
        $this->set_value("isShortCode", true);
        $this->set_value("lapo_type", "airtime_others");
        switch ($this->currentStep) {
            case 1:
                return $this->result("continue", $this->render("confirmation"));
            case 2:
             
                $charge = $this->applySessionCharge();
                    if ($charge->code != 0) {
                        return $this->result('end', $charge->description);
                    }
                if($this->get_value("shortcode_token") == true){
                   
                    return $this->result("continue","Limit of N5,000 exceeded. \nPlease enter token to continue:");
                
                }
                return $this->getAccount($body);
            case 3:
                if($this->get_value("shortcode_token") == true){
                    $this->set_value("cust_token", $this->lastInputed);
                    $token_check = $this->CheckToken($body);
                    
                    if ($token_check == "0" || $token_check == 0){
                        return $this->getAccount($body);
                    }else{
                        return $this->end("Token Error, please try again.");
                    }
                 
                }
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("vendor_voucher"));
            case 4:
                if($this->get_value("shortcode_token") == true){
                    $this->set_value("debit_account", $this->lastInputed);
                    return $this->result("continue", $this->render("vendor_voucher"));
                }
                if (!in_array($this->lastInputed, ['1', '2', '3','4'])){ 
                    return $this->cancelLastAction("You enter an invalid option. \n" . $this->render("vendor_voucher"));
                } 
                $this->set_value("dest_mno", $this->lastInputed);
                $content_amount = $this->get_value("content_three");
                $amount_display = number_format($content_three,2);
                // $msisdn = '234' . substr($this->get_value("content_four"), -10);
                $msisdn = '234' . substr($content_four, -10);
                return $this->result("continue", "You are about to buy airtime for \nPhone Number: $msisdn Amount: N$amount_display\n" . $this->render("pin"));
            case 5:
                if($this->get_value("shortcode_token") == true){
                    if (!in_array($this->lastInputed, ['1', '2', '3','4'])){ 
                        return $this->cancelLastAction("You enter an invalid option. \n" . $this->render("vendor_voucher"));
                    } 
                    $this->set_value("dest_mno", $this->lastInputed);
                    $content_amount = $this->get_value("content_three");
                    $amount_display = number_format($content_three,2);
                    // $msisdn = '234' . substr($this->get_value("content_four"), -10);
                    $msisdn = '234' . substr($content_four, -10);
                    return $this->result("continue", "You are about to buy airtime for \nPhone Number: $msisdn Amount: N$amount_display\n" . $this->render("pin"));
                }
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) { 
                    return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                }elseif($validate == "Successful"){
                    return $this->purchaseForOther($body,$content_three,$content_four);
                }else{
                    return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                }  
            case 6:
                //only happens for token
                if($this->get_value("shortcode_token") == true){
                    $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                    $this->set_value("pin", $this->lastInputed);
                    $validate = $this->PIN($body);
                    if (strlen($pin) != 4) { 
                        return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                    }elseif($validate == "Successful"){
                        return $this->purchaseForOther($body,$content_three,$content_four);
                    }else{
                        return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                    }  
                }    
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

    public function purchaseForOther($body,$content_three,$content_four){
        $pin = $this->get_value("pin");
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $mno = $this->get_value("dest_mno");
        // $content_four = $this->get_value("content_four");
        // $content_three = $this->get_value("content_three");

         
        // if ($mno == "1") {
        //     $dest_mno = 'mtn';
        // }elseif($mno == "2"){
        //     $dest_mno = 'glo';
        // }elseif($mno == "3"){
        //     $dest_mno = 'airtel';
        // }else{
        //     $dest_mno = '9mobile ';
        // }

        switch ($mno) {

            case '3':
                $payment_code = '90106';
                break;
            
            case '4':
                $payment_code = 'paymentCode';
                break;
            case '1':
                $payment_code = '10906';
                break;
            case '2':
                $payment_code = '91309';
                break;
            default:
                # code...
                break;
        }


        // "{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"debit_account\":\"$debit_account\",\"dest_mno\":\"$dest_mno\",\"amount\":$content_three,\"dest_number\":\"$content_four\"}"

        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);

        $post_data = json_encode([
            'phone' => $msisdn,
            'pin' => $pin,
            'debit_account' => $debit_account,
            'payment_code' => $payment_code,
            'amount' => (int)$content_three,
            'dest_number' => $content_four
        ]);

        $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/quickteller-airtime",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return "an error occured\n Please try again";
            }elseif($response) {
                $request = json_decode($response);
                if(isset($request->message)){
                    $message = $request->message;
                    return $this->result("end", "LAPO Swift Banking\n$message"); 
                }
                $message = $request->description;
                return $this->result("end", "LAPO Swift Banking\nTransaction $message");
            }else{
                return $this->result("end","Something went wrong\n Please try again");
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


    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml"));
        return $arr['pages'][$key];
    }
}

?>