<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;
use App\Constants\ApiKey;
use App\Constants\UrlConstant;

class FetchAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:access_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Basic '.ApiKey::$loginMonnifyApi,
        ];
        $apiResponse= Http::withHeaders(
            $headers
        )->post(UrlConstant::$loginMonnifyUrl);
        // Convert the API response to an array
        $data = json_decode($apiResponse, true);

        // Convert the array to a YAML string
        $yaml = Yaml::dump($data);

        // Write the YAML string to a file
        file_put_contents('/Users/mac/Development/ussd/ussed_laravel/storage/app/public/responseFile/api-response.yml', $yaml);    
        
    }
}
