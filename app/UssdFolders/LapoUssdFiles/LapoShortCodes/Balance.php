<?php

namespace App\UssdFolders\LapoUssdFiles\LapoShortCodes;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;

class Balance extends Lapo
{
    public function AccountBalance($body)
    {
        $this->initValues($body);
        $this->set_value('isShortCode', true);
        $this->set_value('lapo_type', 'balance');
        switch ($this->currentStep) {
            case 1:
                return $this->result('continue', $this->render('confirmation'));
            case 2:
                // print('balance charge successful');
                // $charge = $this->applySessionCharge();
                //     if ($charge->code != 0) {
                //         return $this->result('end', $charge->description);
                //     }

                return $this->getAccount($body);
            case 3:
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5'])) {
                    return $this->cancelLastAction("You selected an invalid account. \n".$this->getAccount($body));
                } else {
                    $this->set_value('selected_account', $this->lastInputed);

                    return $this->result('continue', "You will be charged N10 for this service.\nPlease enter your 4 Digit Pin:");
                }
                // no break
            case 4:
                $this->set_value('pin', $this->lastInputed);
                $this->set_value('status', true);

                return $this->checkBalance($body);

            default:
                return $this->cancelLastAction($this->render('account_number'));
        }
    }

    public function getAccount($body)
    {
        $curl = curl_init();
        $msisdn = '0'.substr($this->posted['msisdn'], -10);

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/accounts-by-phone?phone=$msisdn",
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
        $yummy = json_decode($response);

        if ($err) {
            $this->result('end', 'An error occured. Please try again after some time.');
        }

        if ($yummy->code != 0) {
            return $this->result('end', "Unable to fetch account\n".$yummy->message);
        }

        $count = 1;
        $arr = [];
        foreach ($yummy->items as $account) {
            array_push($arr, $count.'. '.$account->ACCOUNT_NUMBER.' ');
            $this->set_value($count, $account->ACCOUNT_NUMBER);
            ++$count;
        }

        $res = implode("\n", array_values($arr));

        return $this->result('continue', "Select Account:\n$res\n00. Back");
    }

    public function checkBalance($body)
    {
        $msisdn = '0'.substr($this->posted['msisdn'], -10);
        $account_selected = $this->get_value('selected_account');
        $debit_account = $this->get_value($account_selected);
        $pin_number = $this->get_value('pin');
        $content = date('d-M-Y h:i:s: ');
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/balance?phone=$msisdn&pin=$pin_number&account_number=$debit_account",
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
            return $this->result('end', "An error occured\n please try again");
        } elseif ($response) {
            $decoded_data = json_decode($response);

            if ($decoded_data->code != null && $decoded_data->code != 0) {
                $description = $decoded_data->description;

                return $this->result('end', "$description");
            }

            if ($decoded_data->code == 0) {
                $account = $decoded_data->account_number;
                $amount = number_format($decoded_data->available_balance, 2);
                $cur_amount = number_format($decoded_data->current_balance, 2);

                return $this->result('end', "LAPO Swift Banking\nAccount Number: $account \nAvailable Balance: NGN$amount\nCurrent Balance: NGN$cur_amount");
            }

            return $this->result('end', "Service temporarily unavailable\n Please try again later");
        } else {
            return $this->result('end', "Service temporarily unavailable\n Please try again");
        }
    }

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents('/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml'));

        return $arr['pages'][$key];
    }
}
