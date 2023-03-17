<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classes\LASM;
use Exception;

class BVNVerifyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function verify(Request $request)
    {
        $bvn = $request->input('bvn');
        $post_var = [
            'BVN' => $bvn,
        ];

        try {
            //$base_url = 'http://10.0.0.184:8023';
            $url = LASM::$base_url.'/bvn/verify';
            //echo 'BVN Web Service Endpoint: '.$url.PHP_EOL;
            $payload = json_encode($post_var);
            //echo 'Payload: '.$payload.PHP_EOL;
            $json = LASM::load_credentials();

            //$user_id = LASM::get_user_id();
            //$param = (object) json_decode($row['SRC_DATA']);
            $param = (object) json_decode($json);
            //$token_id = trim($param->access_token).$user_id;
            //echo 'Access Token: '.$param->access_token.PHP_EOL;
            $key = trim($param->aesKey);
            $iv = trim($param->aesIv);
            $method = 'POST';
            $encrypted_payload = LASM::encrypt($payload, $key, $iv);
            //echo 'Encrypted Payload: '.$encrypted_payload.PHP_EOL;
            // call bvn api
            $curl = curl_init();
            $x_payload = [
        'xPayload' => $encrypted_payload,
    ];
            $json_body = json_encode($x_payload);
            //echo 'xPayload: '.$json_body.PHP_EOL;
            curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_POSTFIELDS => $json_body,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'cache-control: no-cache',
        'Content-Type: application/json',
        'x-lapo-eve-proc: '.$param->access_token,
    ], ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:'.$err;
            //return null;
            } else {
                //return self::decrypt($response, $key, $iv);
                $resp = (object) json_decode($response);
                //var_dump($resp);
                if (isset($resp->data)) {
                    $decrypted = LASM::decrypt($resp->data, $key, $iv);

                    $result = json_decode($decrypted);

                    return response()->json($result);
                    
                }

                return response()->json($resp);
            }

            //var_dump($response);
        } catch (Exception $e) {
            return response()->json(['']);
        }
    }
}
