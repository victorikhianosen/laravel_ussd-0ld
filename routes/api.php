<?php

use App\Http\Controllers\pcashOracle;
use App\UssdFolders\OneXBet\bet1xshortcodes\Topup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StartUssd;
use App\Http\Controllers\LASMKeyGenController;
use React\Socket\ConnectionInterface;
use React\EventLoop\Loop;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/startUssd', [StartUssd::class,'index']);

Route::get('/connection',function (){
    // $conn = DB::connection('oracle')->select('SELECT * FROM DBA_DB_LINKS');
    //  $conn = DB::connection('oracle')->select("SELECT * FROM ALL_DB_LINKS WHERE DB_LINK_NAME = 'SEPMCK'");
        //   $conn = DB::connection('oracle')->select('select * from cat@"SEPMCK.LAPO-NIGERIA.ORG" FETCH FIRST 20 ROW ONLY');
        //   $conn = DB::connection('oracle')->select('select * from cat@"SEPMCK.LAPO-NIGERIA.ORG"');
        // access individual account 
        $conn = DB::connection('oracle')->select('SELECT * FROM DETB_LAPO_THRIFT_CORR_21NOV@"SEPMCK.LAPO-NIGERIA.ORG"');  

    // $conn=DB::connection('oracle')->select('select * from dept');

     return $conn;
});

Route::get('/connecting',[pcashOracle::class,'oraclePcash']);

Route::get('/length',function(){
    return count(['l_ref','p_branch','l_calendar_date','p_branch','p_branch','p_local_currency_amount','p_local_currency_amount','l_custno','p_debit_account','p_branch',
    'p_local_currency_amount','p_local_currency_amount','p_credit_account',
    'p_narration','l_user_id','l_module_id','p_narration','l_trans_code','l_trans_code']);
});

Route::get('/hash', function(){
    $requestHash = 'W+Uz8&IdzGgVS>r>@^ts5I,wh7:K+r`*@XMy6nG23@N6qt,~PSd?nte=qQr>P!';
    $payload = [
        'data' => [
            'type'       => 'exists-user',
            'attributes' => [
                'login'    => "ussd_nigeria_1x",
                'password' => "GO3zHcAeFySmyUKl",
                'phone_code' => 234,
                'phone' => "7066192100",
            ],
        ],
    ];

    $payloadJson = json_encode($payload);

    $signature = hash_hmac('sha256', $payloadJson, $requestHash);
    $headers = [
        "request-signature: {$signature}",
        'Content-Type: application/vnd.api+json',
        'Accept: application/vnd.api+json',
    ];
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://aux-one.com/api/ussd/users/exists");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    // return $result;
    return base64_encode("MK_PROD_8UCFZHDHL6:9AU7TEXMPYV5JKDHT25ZNNM85SH999Y7");
    // return base64_encode("MK_PROD_YW72C4QYWN:P7UWQNANAJNBQPYZB7MBLGK7AM4UULXT");

});

Route::get('/checking',function (){

    $random = substr(md5(mt_rand()), 0, 15);
    return $random;
});

// Route::get('/connectWebsocket',function(){

// $loop = Loop::get();
// $connector = new React\Socket\Connector($loop);


// $stdin = new \React\Stream\ReadableResourceStream(STDIN, $loop);
// $stdout = new \React\Stream\WritableResourceStream(STDOUT, $loop);

// $connector
//     ->connect('127.0.0.1:8888')
//     ->then(
//         function (ConnectionInterface $conn) use ($stdout, $stdin) {
//             $stdin->pipe($conn)->pipe($stdout);
//         },
//         function (Exception $exception) use ($loop){
//             // reject
//         });

// $loop->run();

// });

//to change folder permission // sudo chown -R gbemi:gbemi