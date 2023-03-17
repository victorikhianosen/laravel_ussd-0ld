<?php
namespace App\UssdFolders\LapoUssdFiles\LapoMainFiles;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;

class AirtimePurchase extends Lapo
{

    public function purchaseAirtime($body)
    {
        
        $this->initValues($body);
        
        switch ($this->currentStep) {
            case 3:
             
                return $this->result("continue", $this->render("airtime_for"));
            case 4:
                if (!in_array($this->lastInputed, ['1', '2'])) {
                    return $this->cancelLastAction("You entered an invalid option. \n" . $this->render("airtime_for"));
                }
                switch ($this->lastInputed) {
                    case 1:
                        $this->set_value("action", "airtime_for_me");
                        return $this->aritime_menu($body);
                    case 2:
                        $this->set_value("action", "aritime_others");
                        return $this->aritime_menu($body);
                    default:
                        return $this->cancelLastAction($this->render("airtime_for"));
                }
            default:
                return $this->aritime_menu($body);
        }

    }
    public function aritime_menu($body)
    {

        switch ($this->get_value("action")) {
            case 'airtime_for_me':
                return $this->Self($body);
                
            
            case 'aritime_others':
                return $this->Others($body);
            
            default:
                # code...
                break;
        }
    }

    public function Self($body)
    {
        switch ($this->currentStep) {
            case 4:
                $this->set_value("dest_number", '0' . substr($this->posted["msisdn"], -10));
                return $this->getAccount($body);
            case 5:
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5'])) {
                    return $this->cancelLastAction("You entered an invalid option.\nSelect Account\n" . $this->getAccount($body));
                } else {
                    $this->set_value("debit_account", $this->lastInputed);
                    return $this->result("continue", $this->render("transfer_amount"));
                }
            case 6:
                $amount = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                if ($amount < 50) {
                    return $this->cancelLastAction("You entered an amount too low. \n" . $this->render("transfer_amount"));
                } elseif($amount > 20000){
                    return $this->cancelLastAction("Limit of N20,000 exceeded. \n" . $this->render("transfer_amount"));
                }else {
                    $this->set_value("amount", $amount);
                    $amount_display = number_format($amount, 2);
                    return $this->result("continue", "You are about to purchase airtime for self\nAmount: N$amount_display\n" . $this->render("pin"));
                }
            case 7:
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                if (strlen($pin) != 4) {
                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));
                } elseif ($this->PIN($body) == "Successful") {
                    return $this->makePurchase($body);
                } else {
                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                }
        }
    }

    public function Others($body)
    {
        switch ($this->currentStep) {
            case 4:
                return $this->result("continue", $this->render("beneficiary_number"));
            case 5:
                $beneficiary = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                if (strlen($beneficiary) != 11) {
                    return $this->cancelLastAction("You entered an invalid phone number. \n" . $this->render("beneficiary_number"));
                }
                $this->set_value("dest_number", $this->lastInputed);
                return $this->result("continue", $this->render("vendor_voucher"));
            case 6:
                if (!in_array($this->lastInputed, ['1', '2', '3', '4'])) {
                    return $this->cancelLastAction("You enter an invalid option. \n" . $this->render("vendor_voucher"));
                }
                $this->set_value("dest_mno", $this->lastInputed);
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                $amount = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                if ($amount < 50) {
                    return $this->cancelLastAction("You entered an amount too low. \n" . $this->render("transfer_amount"));
                }elseif($amount > 20000){
                    return $this->cancelLastAction("Limit of N20,000 exceeded. \n" . $this->render("transfer_amount"));
                }
                $this->set_value("amount", $amount);
                return $this->getAccount($body);

            case 8:
                $this->set_value("debit_account", $this->lastInputed);
                $amount = $this->get_value("amount");
                $amount_display = number_format($amount, 2);
                $msisdn = '234' . substr($this->get_value("dest_number"), -10);
                return $this->result("continue", "You are about to purchase airtime for\nPhone Number: $msisdn, Amount: N$amount_display\n" . $this->render("pin"));
            case 9:

                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));
                }

                if ($this->PIN($body) == "Successful") {

                    return $this->makePurchase($body);

                } else {

                    return $this->cancelLastAction("Invalid PIN entered by customer\n" . $this->render("pin"));

                }
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
            if (isset($request->message)){
                $message = $request->message;
                return $message;
            }
            if (isset($request->description)){
                $message = $request->description;
                return $message;
            }
            return "An error occured during execution\n Please try again";
            // if ($request->code == 0) {
                
            //     return $request->description;
            // }

            // return $request->description;
    
        } else {
            return "Service temporarily unavailable\n Please try again";
        }
    }

    public function makePurchase($body)
    {
        $pin = $this->get_value("pin");
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        $amount = $this->get_value("amount");
        $dest_mno = $this->posted["src"];
        $dest_number = $this->get_value("dest_number");
        $mno = empty($this->get_value("dest_mno")) ? $this->posted["src"] : $this->get_value("dest_mno");
        // $payment_code;

        switch ($mno) {
            case '1':
                # mtn
                $payment_code = '10906';
                break;
            case '2':
                # Glo
                $payment_code = '91309';
                break;
            case '3':
                # airtel
                $payment_code = '90106';
                break;
            case '4':
                # 9mobile.
                $payment_code = 'paymentCode';
                break;
            case 'airtel':
                $payment_code = '90106';
                break;
            
            case 'etisalat':
                $payment_code = 'paymentCode';
                break;
            case 'mtn':
                $payment_code = '10906';
                break;
            case 'glo':
                $payment_code = '91309';
                break;
            default:
                # code...
                break;
        }

        $curl = curl_init();

        $post_data = json_encode([
            'phone' => $msisdn,
            'pin' => $pin,
            'debit_account' => $debit_account,
            'payment_code'=> $payment_code,
            'dest_mno' => $dest_mno,
            'amount' => (int)$amount,
            'dest_number' => $dest_number
        ]);
        
       
        // die();
        // http://10.0.0.140:8080/ords/pcash/fcubs/quickteller-airtime
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
                "Content-Type: application/json",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $this->result("end", "An error occured\n Please try again");

        } elseif ($response) {

            $response_data = json_decode($response);
            if (isset($response_data->message)){
                $message = $response_data->message;
                return $this->result("end", "LAPO Swift Banking\n$message");
            }
            if (isset($response_data->description)){
                $message = $response_data->description;
                return $this->result("end", "LAPO Swift Banking\n$message");
            }
            return $this->result("end", "An error occured during execution\n Please try again");
        } else {

            return $this->result("end", "Service temporarily unavailable\n Please try again");
        }
    }


    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml"));
        return $arr['pages'][$key];
    }
}
