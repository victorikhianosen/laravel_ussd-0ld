<?php

namespace App\Classes;

define('CREDENTIAL_FILE', '/Users/mac/Development/ussd/ussed_laravel/storage/app/lasm_credential.json');
define('CREDENTIAL_USER_ID', '136606c50f285d3c9fa89465c2f2');

class LASM
{
    public static $base_url = 'http://10.0.0.184:8023';

    public static function make_request($service, $payload, $access_code, $key, $iv, $method = 'POST')
    {
        switch (strtoupper($method)) {

            case 'GET':
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL => self::$base_url.$service,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'accept: application/json',
                'x-lapo-eve-proc: '.$access_code,
            ], ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                //echo 'cURL Error #:'.$err;
                return null;
            } else {
                return self::decrypt($response, $key, $iv);
            }

            case 'POST':
            $encrypted = self::encrypt($payload, $key, $iv);
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL => self::$base_url.$service,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $encrypted,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'accept: application/json',
                'x-lapo-eve-proc: '.$access_code,
            ], ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                //echo 'cURL Error #:'.$err;
                return null;
            } else {
                return self::decrypt($response, $key, $iv);
            }


            default:
             return null;
        }
    }

    public static function update_credentials($data)
    {
        file_put_contents(CREDENTIAL_FILE, $data);
    }

    public static function load_credentials()
    {
        $json = file_get_contents(CREDENTIAL_FILE);

        return $json;
    }

    public static function get_user_id()
    {
        return CREDENTIAL_USER_ID;
    }

    public static function decrypt($data, $key, $iv)
    {
        $encrypted = hex2bin($data);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function encrypt($data, $key, $iv)
    {
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return bin2hex($encrypted);
    }
}
