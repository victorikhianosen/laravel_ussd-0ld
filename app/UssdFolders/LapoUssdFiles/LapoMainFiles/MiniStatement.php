<?php

namespace App\UssdFolders\LapoUssdFiles\LapoMainFiles;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;
use Exception;

class MiniStatement extends Lapo
{
    public function Statement($body)
    {
        $this->initValues($body);
        switch ($this->currentStep) {
            case 3:
                return $this->getAccount($body);

            case 4:
                if (!in_array($this->lastInputed, ['1', '2', '3', '4', '5'])) {
                    return $this->cancelLastAction("You entered an invalid account option. \n".$this->getAccount($body));
                } else {
                    $this->set_value('account_number', $this->lastInputed);

                    return $this->result('continue', $this->render('mini_statement'));
                }
                // no break
            case 5:
                $this->set_value('pin', $this->lastInputed);
                $pin = preg_replace("/[^-0-9\.]/", '', $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {
                    return $this->cancelLastAction("Invalid PIN entered by customer\n".$this->render('pin'));
                } elseif ($validate == 'Successful') {
                    $min = $this->QueryStatement($body);

                    return $this->result('end', "LAPO Swift Banking\n$min");
                } else {
                    return $this->cancelLastAction("Invalid PIN entered by customer\n".$this->render('pin'));
                }
                // no break
            default:
                $min = $this->QueryStatement($body);
        }
    }

    public function PIN($body)
    {
        $pin = $this->get_value('pin');
        $msisdn = '0'.substr($this->posted['msisdn'], -10);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/auth?phone=$msisdn&pin=$pin",
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
            return "an error occured\n please try again";
        } elseif ($response) {
            $request = json_decode($response);
            $description = $request->description;

            return $description;
        } else {
            return "Service temporarily unavailable\n Please try again";
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
        $resp = json_decode($response);

        if ($err) {
            $this->result('end', 'An error occured. Please try again after some time.');
        }

        if ($resp->code != 0) {
            return $this->result('end', "Unable to fetch account\n".$resp->message);
        }

        $count = 1;
        $arr = [];
        foreach ($resp->items as $account) {
            array_push($arr, $count.'. '.$account->ACCOUNT_NUMBER.' ');
            $this->set_value($count, $account->ACCOUNT_NUMBER);
            ++$count;
        }

        $res = implode("\n", array_values($arr));

        return $this->result('continue', "Select Account:\n$res");
    }

    public function QueryStatement($body)
    {
        $account_selected = $this->get_value('account_number');
        $account_number = $this->get_value($account_selected);
        $msisdn = '0'.substr($this->posted['msisdn'], -10);
        $pin = $this->get_value('pin');
        // put critical code in try block and handle exception gracefully
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/transactions?phone=$msisdn&pin=$pin&account_number=$account_number",
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
                $this->result('end', 'An error occured. Please try again after some time.');
            }

            $resp = json_decode($response);
            $count = 1;
            $arr = [];
            // ensure proper type conversion
            $resp_code = (int) $resp->code;

            if ($resp_code != 0) {
                // proper type check
                $message = isset($resp->description) ? (string) $resp->description : (string) $resp->message;

                return "Unable to get mini statement\n".$message;
            }
            $items = $resp->items;
            if (count($items) > 0) {
                foreach ($items as $account) {
                    array_push($arr, $count.'.)'.'DT: '.$account->TXNDT.' Amt: NGN '.number_format($account->LCYAMT, 2).' T: '.$account->DRCR.'r');
                    ++$count;
                }

                return implode("\n", array_values($arr));
            } else {
                $message = isset($resp->description) ? (string) $resp->description : (string) $resp->message;

                return "Unable to get mini statement\n".$message;
            }
        } catch (Exception $e) {
            return 'Unable to get Mini statment at this time. Please try again later';
        }
    }

    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents('/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml'));

        return $arr['pages'][$key];
    }
}
