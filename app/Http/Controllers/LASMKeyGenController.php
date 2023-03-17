<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classes\Oracle;


class LASMKeyGenController extends Controller
{
    private $db;

    public function keygen(Request $request)
    {
        //echo 'Generating new LASM user key.'.PHP_EOL;
        // all transactions last 2 hours
        //$days = (int) $this->argument('days');
        try {
            //$conn = $this->db_connect();
            $this->db = new Oracle();
            $sql = 'select * from app_config where tag=lasm';
            $rows = $this->db->query($sql);
            $params = [];
            foreach (array_values($rows) as $row) {
                //echo $row['NAME'].': '.$row['VAL'].PHP_EOL;
                $params[$row['NAME']] = $row['VAL'];
            }
            //var_dump($params);
            $token_id = trim($params['lasm_token']) . trim($params['lasm_user_id']);
            $key = trim($params['lasm_aes_key']);
            $iv = trim($params['lasm_aes_iv']);
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'http://10.0.0.184:8023/generateuserkey',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'cache-control: no-cache',
                    'accept: application/json',
                    'x-lapo-eve-proc: ' . $token_id,
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return 'cURL Error #:' . $err;
            } else {
                //echo $response.PHP_EOL;
                $this->log($response);
                $resp = (object) json_decode($response);
                //echo $resp->status.PHP_EOL;
                if (isset($resp->data)) {
                    $decrypted_data = $this->decrypt($resp->data, $key, $iv);
                    $this->log($decrypted_data);
                    return $decrypted_data;
                    //$this->update_credentials($decrypted_data);
                }
                //echo $response.PHP_EOL;
            }
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function update_credentials($decrypted_data)
    {
        //echo 'Updating credentials..'.PHP_EOL;
        $cred = (object) json_decode($decrypted_data);
        $db = $this->db;
        $bind = [
            'aes_key' => $cred->aesKey,
        ];
        $db->execute('begin update app_config set val= :aes_key where name=lasm_aes_key;
    exception when others then null;
    end;
    ', $bind);
        $bind = [
            'aes_iv' => $cred->aesIv,
        ];
        $this->db->execute('begin
    update app_config set val= :aes_iv where name=lasm_aes_iv;
    exception when others then null;
    end;
    ', $bind);
        $bind = [
            'access_token' => $cred->access_token,
        ];
        $this->db->execute('begin
    update app_config set val = :access_token where name=lasm_token;
    exception when others then null;
    end;
    ', $bind);
        echo 'Credentials updated' . PHP_EOL;
    }


    public function decrypt($data, $key, $iv)
    {
        $encrypted = hex2bin($data);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

// public function log($request)
// {
//     file_put_contents('/var/www/html/lapo/logs/lasm_log_'.date('Y_m_d').'.log', $request.PHP_EOL, FILE_$);
// }

public function connection(){
    $this->db = new Oracle();
    return $this->db->testConnection();
}
}