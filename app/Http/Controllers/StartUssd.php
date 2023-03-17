<?php

namespace App\Http\Controllers;
use App\Support\InitializeUssd;
use Illuminate\Http\Request;
use App\Http\Controllers;


class StartUssd extends Controller
{
    // this is where the ussd starts
    public function index($body){
    //  $body = json_decode(file_get_contents('php://input'), true);
        $src_msisdn = $body['msisdn'];
        //return $request;
        // first try to charge for pre-paid sessions services
    
        // $wallet = InitializedUssd::get_prepaid($body);
    
        // if ($wallet) {
        //     // charge the prepaid service owner
        //     if (InitializedUssd::charge_prepaid($wallet)) {
        //         $body['action'] = 'Continue';
        //     } else {
        //         // prepaid service but we could not charge the service owner
        //         // we terminate the session
        //         $body['reply'] = 'This service has expired';
        //         $body['action'] = 'End';
    
        //         return $response->withJson($body);
        //     }
        // }
       
        $userKey = InitializeUssd::getUserKey($body['msisdn']); // this returns user:msidn as key for redis 
        $inputKey = InitializeUssd::getInputKey($body['msisdn']);
        $commandID = InitializeUssd::getCommand($body['commandID']); 
        $InitializedUssd= new InitializeUssd();
        // get keyword for command
        switch ($commandID) {
            case 'BEGIN':
                // cache session
               
                $InitializedUssd->beginSession($body);
                break;
            case 'CONTINUE':
                InitializeUssd::saveInput($body);
                break;
            case 'SWITCH_BEGIN':
                // update inputs
                $InitializedUssd->beginSession($body);
                break;
            default:
                $InitializedUssd->beginSession($body);
        }
        //InitializedUssd::logToFile($body);
        $inputs = InitializeUssd::getUserInputs($inputKey); // get stored user input from redis
        $handler = InitializeUssd::getHandler($inputs[0]);
        // print_r($inputs);
        $ussdApplication = null;
        // Dynamic Class
        require_once "{$handler}.php";
        $class_name= "App\\Http\\Controllers\\".$handler;
        $ussdApplication =new $class_name;
        //$body['reply'] = "User inputs " . implode(":", $inputs);
        // we supply the input and user object
        $ussdResponse = $ussdApplication->getResponse($body);
        $body['reply'] = $ussdResponse['message'];
        $body['action'] = $ussdResponse['action'];
        if (!empty($ussdResponse['destServiceCode'])) {
            $body['destServiceCode'] = $ussdResponse['destServiceCode'];
            $body['srcServiceCode'] = $ussdResponse['srcServiceCode'];
        }
        if ($body['reply'] == 'UNKNOWN APPLICATION') {
        } else {
            // avoid session mix up
            if ($body['msisdn'] == $src_msisdn) {
                return $body;
            }
        }
        //$body["serviceCode"] = InitializedUssd::getPersistedServiceCode($body);
    
        $body['reply'] = 'Something unusual happened. Please try again later';
    
        return $response->withJson($body);
    }
}
