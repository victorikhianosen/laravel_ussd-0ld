<?php

namespace App\Http\Controllers;

use App\UssdFolders\LapoUssdFiles\LapoMainFiles\AirtimePurchase;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\CheckAccountBalance;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\CreateAccount;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\CreatePin;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\Data;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\MiniStatement;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\PayBills;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\SelfService;
use App\UssdFolders\LapoUssdFiles\LapoMainFiles\TransferFunds;

use App\UssdFolders\LapoUssdFiles\LapoShortCodes\AirtimeOthers;
use App\UssdFolders\LapoUssdFiles\LapoShortCodes\AirtimeSelf;
use App\UssdFolders\LapoUssdFiles\LapoShortCodes\DataOthers;
use App\UssdFolders\LapoUssdFiles\LapoShortCodes\DataSelf;
use App\UssdFolders\LapoUssdFiles\LapoShortCodes\BankStatement;
use App\UssdFolders\LapoUssdFiles\LapoShortCodes\Transfer;
use App\UssdFolders\LapoUssdFiles\LapoShortCodes\Balance;


use Symfony\Component\Yaml\Yaml;
use App\Support\UssdApplication;
use Exception;

class Lapo extends UssdApplication
{
    public function getResponse($body)
    {
        $this->initValues($body);
        // back functionality can come here
        if ($this->lastInputed === '00') {
            return $this->back();
        }

        //stop cacheing
        $content = $this->posted['content'];
        $exploded = explode('*', $content); // split string at *
        $remove = preg_replace("/[^-0-9\.]/", '', $exploded);
        // to replace any character that is not -, digit or period with empty string
        $content3 = @$remove[2]; // @ is used to suppress error like undefined variable
        $content4 = @$remove[3];

        if ($content3 >= 20 && strlen($content4) == 11) {
            $check = 'airtime_others';
        } elseif ($content3 == '5' && strlen($content4) == 11) {
            $check = 'data_others';
        } elseif ($content3 == '5') {
            $content4 = $this->get_value('content4');
            if (isset($content4)) {
                $this->delete_value('content4');
            }
            $check = 'data_self';
        } elseif ($content3 >= 20 && strlen($content4) == 10) {
            $check = 'transfer';
        } elseif (strlen($content3) == 10) {
            $content4 = $this->get_value('content4');
            if (isset($content4)) {
                $this->delete_value('content4');
            }
            $check = 'bankstatement';
        } elseif ($content3 >= 20 && !isset($content4)) {
            $content4 = $this->get_value('content4');
            if (isset($content4)) {
                $this->delete_value('content4');
            }
            $check = 'airtime_self';
        } elseif ($content3 == '4') {
            $content4 = $this->get_value('content4');
            if (isset($content4)) {
                $this->delete_value('content4');
            }
            $check = 'balance';
        }

        if (isset($check)) {
        }
        // this is where the code knows if a short code is dailed or not
        if (isset($check) && !empty($check)) {
            if ($check != $this->get_value('lapo_type')) {
                $this->set_value('lapo_type', $check);
                $this->set_value('isShortCode', true);
            }
        }

        // if an ordinary code is dailed, then all short code 
        // saved in redis is 
        if ($this->posted['content'] == '*371#') {
            // Restarting a new sesson clear all values
            $this->delete_value('lapo_type');
            $this->delete_value('isShortCode');
            $this->delete_value('lapo_action');
            $this->delete_value('content3');
            $this->delete_value('content4');
            $this->delete_value('shortcode_option');
            $this->delete_value('menu_option');
        }


        // check if user is registered and has a pin

        $register = $this->registeredCustomer($body);
        $checkCustomer = $this->checkCustomer($body);




        // check if some of this value are assign and go straight to the pages
        $shortcode = $this->get_value('isShortCode');
        $open = $this->get_value('Open');
        $pin = $this->get_value('Pin');
        // var_dump($pin); exit;
        if ($shortcode == true) {
            return $this->checkInputContent($body);
        } elseif ($open == true) {
            return $this->OpenAccount($body);
        } elseif ($pin == true) {
            return $this->CreatePin($body);
        }

        switch ($this->currentStep) {
            case 1:
                // we should charge here before anythin else
                if ($checkCustomer == 'Successful' && $register != 'Successful') {
                    $pin = new CreatePin();
                    return $pin->AddPin($body);
                }
                //    first check if ther is an error
                if ($register != 'Successful' && $register != 'Customer with phone number does not exist') {
                    //return $this->result('end', 'System is unavailable. Please try again later');
                    return $this->result('end', $register);
                }

                // check if user is not registered
                if ($register != 'Successful' && $register == 'Customer with phone number does not exist') {
                    $create = new CreateAccount();
                    return $create->openAccount($body);
                } elseif ($register == 'Successful') {
                    return $this->result('continue', $this->render('confirmation'));
                }

            case 2:
                return $this->checkInputContent($body);
            case 3:
                // $phone = '0' . substr($this->posted["msisdn"], -10);
                // $client = new GuzzleHttp\Client();
                // $res = $client->request('GET', "https://pcash.ng/ords/pcash/fcubs/enable-activities?phone=$phone");
                // $result = json_decode($res->getBody());
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5', '6', '7', '8'])) {
                    return $this->cancelLastAction("You entered an invalid option. \n" . $this->render('main_menu'));
                }
                switch ($this->lastInputed) {
                    case 1:


                        $this->set_value('lapo_action', 'buy_airtime');

                        return $this->lapo_menu($body);
                    case 2:

                        $this->set_value('lapo_action', 'funds_transfer');

                        return $this->lapo_menu($body);
                    case 3:
                        $this->set_value('lapo_action', 'bill_payment');

                        return $this->lapo_menu($body);
                    case 4:
                        $this->set_value('lapo_action', 'account_balance');

                        return $this->lapo_menu($body);
                    case 5:

                        $this->set_value('lapo_action', 'data');

                        return $this->lapo_menu($body);
                    case 6:
                        $this->set_value('lapo_action', 'statement');

                        return $this->lapo_menu($body);
                    case 7:
                        $this->set_value('lapo_action', 'service');

                        return $this->lapo_menu($body);
                    case 8:
                        $this->set_value('lapo_action', 'loan');

                        return $this->lapo_menu($body);
                    default:
                        
                        // charge here for registered users before proceeding
                        // $charge = $this->applySessionCharge();
                        // if ($charge->code != 0) {
                        //     return $this->result('end', $charge->description);
                        // }
                        $this->cancelLastAction($this->render('main_menu'));
                }
            // no break
            default:
                return $this->lapo_menu($body);
        }
    }



    public function CreatePin($body)
    {
        $pin = new CreatePin();

        return $pin->AddPin($body);
    }

    public function OpenAccount($body)
    {
        $create = new CreateAccount();

        return $create->openAccount($body);
    }

    public function registeredCustomer($body)
    {
        $msisdn = '0' . substr($this->posted['msisdn'], -10);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/registered?phone=$msisdn",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
            // return  "an timeout/error occured\n please try again";
        } elseif ($response) {

            $request = json_decode($response);
            $description = $request->description;
            // better to return the error code than the description(which is more likely to change)

            return $description;
        } else {
            return "Something went wrong\n Please try again";
        }
    }

    public function applySessionCharge()
    {
        $msisdn = '0' . substr($this->posted['msisdn'], -10);
        try {
            // Quickly use a GET request
            $result = file_get_contents('http://10.0.0.140:8080/ords/pcash/fcubs/session-charge?phone=' . $msisdn);
            $ret = json_decode($result);

            return $ret;
            if ($ret) {
                return $ret;
            }
        } catch (Exception $e) {
            return $this->result('end', 'A problem occured starting a new session. Please try again later');
        }
    }

    public function checkCustomer($body)
    {
        $msisdn = '0' . substr($this->posted['msisdn'], -10);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/customer-check?phone=$msisdn",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "An error occured\n please try again";
        } elseif ($response) {
            $request = json_decode($response);
            $description = $request->description;

            return $description;
        } else {
            return "Something went wrong\n Please try again";
        }
    }

    public function checkInputContent($body)
    {

        $content = $this->posted['content'];
        $exploded = explode('*', $content);
        $remove = preg_replace("/[^-0-9\.]/", '', $exploded);
        $content_three = @$remove[2];
        $content_four = @$remove[3];
        // var_dump($content_three); exit;
        if ($content_three >= 20) {
            $this->set_value('content3', (string) $content_three);
            if (isset($content_four)) {
                $this->set_value('content4', (string) $content_four);
            }
        }



        $content_four = $this->get_value('content4');
        $content_three = $this->get_value('content3');
        $mode = $this->get_value('lapo_type');

        if ($content == '*371*4#') {
            $this->set_value('shortcode_option', 'balance');
            $statement = new Balance();
            $result = $statement->AccountBalance($body);
            $this->delete_value('lapo_type');

            return $result;
        } elseif ($content_three >= 20 && strlen($content_four) == 11) {
            $this->set_value('shortcode_option', 'airtime_others');
            $airtime = new AirtimeOthers();
            $result = $airtime->AirtimeForOthers($body, $content_three, $content_four);
            $this->delete_value('lapo_type');

            return $result;
        } elseif ($content_three == '5' && strlen($content_four) == 11) {
            $this->set_value('shortcode_option', 'data_others');
            $data_other = new DataOthers();
            $result = $data_other->DataForOthers($body, $content_three, $content_four);
            $this->delete_value('lapo_type');

            return $result;
        } elseif ($content_three == '5') {
            $this->set_value('shortcode_option', 'data_self');
            $data_self = new DataSelf();
            $result = $data_self->Data($body);
            $this->delete_value('lapo_type');

            return $result;
        } elseif ($content_three >= 20 && strlen($content_four) == 10) {

            $this->set_value('shortcode_option', 'transfer');
            if ((int) $content_three > 20000) {
                $this->set_value('shortcode_token', true);
                $transfer = new Transfer();
                $result = $transfer->TransferSteps($body, $content_three, $content_four);
                $this->delete_value('lapo_type');
                $this->delete_value('shortcode_token');
                return $result;
            }
            $transfer = new Transfer();
            $result = $transfer->TransferSteps($body, $content_three, $content_four);
            $this->delete_value('lapo_type');

            return $result;
        } elseif (strlen($content_three) == 10) {
            $this->set_value('shortcode_option', 'statement');
            $statement = new BankStatement();
            $result = $statement->Statement($body);
            $this->delete_value('lapo_type');

            return $result;
        } elseif ($content_three >= 20 && !isset($content4)) {

            $this->set_value('shortcode_option', 'airtime_self');
            $airtime_self = new AirtimeSelf();
            $result = $airtime_self->Airtime($body, $content_three);
            $this->delete_value('lapo_type');

            return $result;
        } else {

            $options = $this->get_value('shortcode_option');
            if (!isset($options)) {
                
                    // charge here for registered users before proceeding
                    // $charge = $this->applySessionCharge();
                    // if ($charge->code != 0) {
                    //     return $this->result('end', $charge->description);
                    // }
                return $this->result('continue', $this->render('main_menu'));
            }

            switch ($options) {
                case 'main':
                   
                    // charge here for registered users before proceeding
                    // $charge = $this->applySessionCharge();
                    // if ($charge->code != 0) {
                    //     return $this->result('end', $charge->description);
                    // }
                    return $this->result('continue', $this->render('main_menu'));
                case 'balance':
                    $statement = new Balance();

                    return $statement->AccountBalance($body);
                case 'airtime_others':
                    $airtime = new AirtimeOthers();

                    return $airtime->AirtimeForOthers($body, $content_three, $content_four);
                case 'data_others':
                    $data_other = new DataOthers();

                    return $data_other->DataForOthers($body, $content_three, $content_four);
                case 'data_self':
                    $data_self = new DataSelf();

                    return $data_self->Data($body);
                case 'transfer':
                    if ((int) $content_three > 20000) {
                        $transfer = new Transfer();
                        $result = $transfer->TransferSteps($body, $content_three, $content_four);
                        $this->delete_value('lapo_type');
                        $this->delete_value('shortcode_token');

                        return $result;
                    }
                    $this->set_value('shortcode_option', 'transfer');
                    $transfer = new Transfer();

                    return $transfer->TransferSteps($body, $content_three, $content_four);
                case 'statement':
                    $statement = new BankStatement();

                    return $statement->Statement($body);
                case 'airtime_self':
                    $airtime_self = new AirtimeSelf();

                    return $airtime_self->Airtime($body, $content_three);

                default:
                    // var_dump($this->get_value("lapo_type"), $this->get_value("isShortCode"), "yes"); exit;
                    return $this->result('End', $this->render('invalid_code'));
            }
        }
    }

    public function lapo_menu($body)
    {
        // $phone = '0' . substr($this->posted["msisdn"], -10);
        // $client = new GuzzleHttp\Client();
        // $res = $client->request('GET', "https://pcash.ng/ords/pcash/fcubs/enable-activities?phone=$phone");
        // $result = json_decode($res->getBody());

        $mode = $this->get_value('lapo_action');
        if ($mode == 'buy_airtime') {
            // if($result->Airtime == "NO"){
            //     return $this->result("end", "This feature is currently not available for this user");
            // }

            $this->set_value('menu_option', 'buy_airtime');
            $airtime = new AirtimePurchase();

            return $airtime->purchaseAirtime($body);
        } elseif ($mode == 'funds_transfer') {
            // if($result->Transfer == "NO"){
            //     return $this->result("end", "This feature is currently not available for this user");
            // }
            $this->set_value('menu_option', 'funds_transfer');
            $funds = new TransferFunds();

            return $funds->transferingFunds($body);
        } elseif ($mode == 'bill_payment') {
            $this->set_value('menu_option', 'bill_payment');
            $bills = new PayBills();

            return $bills->payingBills($body);
        } elseif ($mode == 'account_balance') {
            $this->set_value('menu_option', 'account_balance');
            $balance = new CheckAccountBalance();

            return $balance->balanceInAccount($body);
        } elseif ($mode == 'data') {
            // if($result->Data == "NO"){
            //     return $this->result("end", "This feature is currently not available for this user");
            // }
            $this->set_value('menu_option', 'data');
            $data = new Data($body);

            return $data->BuyData($body);
        } elseif ($mode == 'statement') {
            $this->set_value('menu_option', 'statement');
            $mini = new MiniStatement();

            return $mini->Statement($body);
        } elseif ($mode == 'loan') {
            $this->set_value('menu_option', 'loan');
            $request = new LoanRequestPcash($body);

            return $request->Loan($body);
        } elseif ($mode == 'service') {
            $this->set_value('menu_option', 'service');
            $self = new SelfService();

            return $self->Service($body);
        } else {
            $options = $this->get_value('menu_option');

            switch ($options) {
                case 'buy_airtime':
                    $airtime = new AirtimePurchase();

                    return $airtime->purchaseAirtime($body);
                case 'funds_transfer':
                    $funds = new TransferFunds();

                    return $funds->transferingFunds($body);
                case 'bill_payment':
                    $bills = new PayBills();

                    return $bills->payingBills($body);
                case 'account_balance':
                    $balance = new CheckAccountBalance();

                    return $balance->balanceInAccount($body);
                case 'data':
                    $data = new Data($body);

                    return $data->BuyData($body);
                case 'statement':
                    $mini = new MiniStatement();

                    return $mini->Statement($body);
                case 'loan':
                    $request = new LoanRequestPcash($body);

                    return $request->Loan($body);
                case 'service':
                    $self = new SelfService();

                    return $self->Service($body);
                default:
                    // var_dump($this->get_value("lapo_type"), $this->get_value("isShortCode")); exit;
                    return $this->result('End', $this->render('invalid_code'));
                // return $this->lapo_menu($body);
            }
        }
    }

    public function pay_response($params)
    {
        // $url = BASEURL.'api/v1/process';
        $url = '';
        $client = new GuzzleHttp\Client();
        $res = $client->request('POST', "$url", [
            'json' => $params,
        ]);
        $result = json_decode($res->getBody());
        $type = $result->action;
        switch (strtolower($type)) {
            case 'menu':
                // code...
                return $this->menu($result);
            case 'prompt':
                // code...
                return $this->prompt($result);
            case 'notice':
                // code...
                return $this->notice($result);

            default:
                // code...
                return $this->result('end', 'An error occurred, please try again.');
        }
    }

    public function prompt($result)
    {
        $response = $result->text;

        return $this->result('continue', $response);
    }

    public function menu($result)
    {
        $responses = $result->menu;
        $return_string = '';
        foreach ($responses as $response) {
            $return_string .= $response->value . '. ' . $response->label;
        }

        return $this->result('continue', $return_string);
    }

    public function notice($result)
    {
        $response = $result->statusCode;
        $status = $result->status;

        return $this->result('end', $status);
    }

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents('/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml'));

        return $arr['pages'][$key];
    }
}



