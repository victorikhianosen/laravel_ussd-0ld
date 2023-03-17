<?php

namespace App\UssdFolders\LapoUssdFiles\LapoMainFiles;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;

class Data extends Lapo
{

    public function BuyData($body)
    {
        $this->initValues($body);
        switch ($this->currentStep) {
            case 3:
                return $this->result("continue", $this->render("data_for"));
            case 4:
                if (!in_array($this->lastInputed, ['1', '2'])) {
                    return $this->cancelLastAction("You entered an invalid option. \n" . $this->render("data_for"));
                }
                switch ($this->lastInputed) {
                    case 1:
                        $this->set_value("action", "data_for_me");
                        return $this->aritime_menu($body);
                    case 2:
                        $this->set_value("action", "data_others");
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
        $mode = $this->get_value("action");
        if ($mode == "data_for_me") {
            return $this->Self($body);
        } elseif ($mode == "data_others") {
            return $this->Others($body);
        }
    }

    public function Self($body)
    {
        
        
        switch ($this->currentStep) {
            case 3:

                return $this->getAccount($body);

            case 4:
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5'])) {

                    return $this->cancelLastAction("You entered an invalid option.");

                }

                $this->set_value("debit_account", $this->lastInputed);
                $plan = $this->getDataPlansByNetwork();
                return $this->result("continue", "Select Plan:\n$plan");

            case 5:

                if ($this->lastInputed == '0') {
                    $plan = $this->getDataPlansByNetwork();
                    return $this->cancelLastAction("Select Plan:\n$plan\n00. Back");

                }else {
                    
                    return $this->result("continue", "You are about to purchase a data plan for self\n" . $this->render("pin"));

                }
            case 6:

                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);

                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    // return $this->result("end", "You data purchase is processing..");
                    return $this->PurchaseForMe($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                }
        }
    }

    public function getDataPlansByNetwork($network = null)
    {
        

        if ($network != null) {
            //n1. MTN\n2. GLO\n3. Airtel\n4. 9Mobile\n00.
            switch (strval($network)) {
                case '1':
                    $biller_id = 348;
                    break;
                case '2':
                    $biller_id = 3070;
                    break;
                case '3':
                    $biller_id = 2775;
                    break;
                case '4':
                    $biller_id = 205;
                    break;                  
                default:
                    break;
            }

        }else{

            switch ($this->posted["src"]) {
                case 'airtel':
                    $biller_id = 2775; 
                    break;
                case 'etisalat':
                    $biller_id = 205;
                    break;
                case 'mtn':
                    $biller_id = 348;
                    break;    
                case 'glo':
                    $biller_id = 3070;
                    break;
                default:
                    # code...
                    break;
            }
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140/qt-integration/public/api/get-billitem?biller_id=$biller_id",
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

            return "Unable to fecth data plans, please try again later.";
            
        }

        $data_plans = json_decode($response);
        $count = 1;
        $display = [];

        foreach ($data_plans as $plan) {
            
            array_push($display, strval($count++) .". ". $plan->paymentitemname);
            $this->set_value(strval($count).'-option', $plan->paymentCode);
            $this->set_value(strval($count).'-amount', $plan->amount);

        }

        if ($this->lastInputed == '0') {

            return (implode("\n", array_values(array_slice($display, 6))));

        } else {

            return (implode("\n", array_values(array_slice($display, 0, 6))));

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


    public function DataPlan($body)
    {
        $dest_mno = $this->posted["src"];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/data-plan?mno=$dest_mno",
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
        $count = 1;
        $arr = [];

        foreach ($yummy->items as $mno) {
            $exploded = explode(' ', $mno->short_name);
            $gig = $exploded[1];
            $content = $exploded[2];
            $amount = $mno->amount;
            $edited = str_replace("data", "", $content);
            array_push($arr, $count . ". " . $gig . " " . $edited . " @ N" . $amount . "");
            $this->set_value($count . '-bill_id', $mno->bill_id);
            $this->set_value($count . '-amount', $mno->amount);
            $this->set_value($count . '-gig', $mno->short_name);
            $count++;
        }

        if ($this->lastInputed === '0') {
            return (implode("\n", array_values(array_slice($arr, 9))));
        } else {
            return (implode("\n", array_values(array_slice($arr, 0, 9))));
        }
    }

    public function purchaseForMe($body)
    {
        $pin = $this->get_value("pin");
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        $bill = $this->get_value("bill_id");
        $bill_id = $this->get_value($bill . '-bill_id');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/data-plan",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"dest_number\":\"$msisdn\",\"bill_id\":$bill_id,\"debit_account\":\"$debit_account\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "an error occured\n Please try again";
        } elseif ($response) {
            $request = json_decode($response);
            $description = $request->description;
            return $this->result("end", "LAPO Swift Banking\n$description");
        } else {
            return $this->result("end", "Service temporarily unavailable\n Please try again");
        }
    }

    public function Others($body)
    {

        switch ($this->currentStep) {
            case 3:
                
                return $this->getAccount($body);

            case 4:
                if (!in_array($this->lastInputed, ['1', '2', '3', '4','5'])) {

                    return $this->cancelLastAction("You entered an invalid option. \n". $this->getAccount($body));

                }

                $this->set_value("debit_account", $this->lastInputed);

                return $this->result("continue", $this->render("beneficiary_number"));
            case 5:

                $beneficiary = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                if (strlen($beneficiary) != 11) {
                    return $this->cancelLastAction("You entered an invalid phone number. \n" . $this->render("beneficiary_number"));
                }
                $this->set_value("dest_number", $this->lastInputed);
                return $this->result("continue", $this->render("vendor_voucher"));

            case 6:

                $this->set_value("dest_mno", $this->lastInputed);
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5'])) {

                    return $this->cancelLastAction("You enter an invalid option. \n" . $this->render("vendor_voucher"));
                }

                $plan = $this->getDataPlansByNetwork($this->lastInputed);
                return $this->result("continue", "Select Plan:\n$plan");

            case 7:

                $plan = $this->getDataPlansByNetwork($this->lastInputed);
                $msisdn = '234' . substr($this->get_value("dest_number"), -10);

                if ($this->lastInputed == '0') {

                    return $this->cancelLastAction("Select Plan:\n$plan\n00. Back");

                } elseif ($this->lastInputed == '00') {

                    return $this->cancelLastAction("Select Plan:\n$plan");

                } else {

                    $this->set_value("bill_id", $this->lastInputed);
                    return $this->result("continue", "You are about to purchase a data plan for $msisdn\n" . $this->render("pin"));
                    
                }
            case 8:

                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);

                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing");
                    return $this->purchaseForOther($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));

                }
        }
    }

    public function purchaseForOther($body)
    {

        $pin = $this->get_value("pin");
        $msisdn = '0' . substr($this->posted["msisdn"], -10);
        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        $bill = $this->get_value("bill_id");
        $bill_id = $this->get_value($bill . '-bill_id');
        $dest_number = $this->get_value("dest_number");

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/data-plan",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"dest_number\":\"$dest_number\",\"bill_id\":$bill_id,\"debit_account\":\"$debit_account\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "an error occured\n Please try again";
        } elseif ($response) {
            $request = json_decode($response);
            $description = $request->description;
            return $this->result("end", "LAPO Swift Banking\n$description");
        } else {
            return $this->result("end", "Service temporarily unavailable\nPlease try again");
        }

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

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml"));
        return $arr['pages'][$key];
    }
}
