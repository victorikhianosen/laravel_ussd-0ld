<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use React\EventLoop\TimerInterface;
use React\EventLoop\Loop;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Classes\LASM;

define('CREDENTIAL_FILE', '/Users/mac/Development/ussd/ussed_laravel/lasm_credential.json');
define('CREDENTIAL_USER_ID', 'ea3205488f4b4200b995186966d5');

class ReactScriptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    
    /**
     * Execute the console command.
     *
     * @return int
     */

    protected $signature = 'react:script';
    public  $base_url = 'http://10.0.0.183:8001';

    protected $description = 'Run ReactPHP script that prints a lovely message every 20 seconds';

    public function handle()
    {
        $loop = new Loop();
        $loop->addPeriodicTimer(2, function(TimerInterface $timer) {
           
            try {
                $json = file_get_contents(CREDENTIAL_FILE);
                $user_id = CREDENTIAL_USER_ID;
                //$param = (object) json_decode($row['SRC_DATA']);
                $param = (object) json_decode($json);
                $token_id = trim($param->access_token).$user_id;
                $key = trim($param->aesKey);
                $iv = trim($param->aesIv);

                $response = Http::withHeaders(
                    [
                        'cache-control: no-cache',
                        'accept: application/json',
                        'x-lapo-eve-proc: '.$token_id,
                    ]
                )->get($this->base_url.'/generateuserkey');
                echo $response.PHP_EOL;
                print_r($response);
                $resp = (object) json_decode($response);
                //echo $resp->status.PHP_EOL;
                if (isset($resp->data)) {
                    $decrypted_data = $this->decrypt($resp->data, $key, $iv);
                    //echo $decrypted_data.PHP_EOL;
                    // log when debuging
                    //$this->log($decrypted_data);
                    $this->update_credentials($decrypted_data);

                    echo 'OK';
                }
                $this->line("Data fetched and written to file.");
            } catch (Exception $e) {
                $this->error("Error fetching data: ".$e->getMessage());
            }
        });
        $loop->run();
    }

    public function update_credentials($decrypted_data)
    {
        echo 'Updating credentials..'.PHP_EOL;
        LASM::update_credentials($decrypted_data);
        echo 'Updated successfully'.PHP_EOL;
    }


    public function decrypt($data, $key, $iv)
    {
        $encrypted = hex2bin($data);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

}

