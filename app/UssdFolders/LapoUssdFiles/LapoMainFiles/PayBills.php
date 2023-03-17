<?php
namespace App\UssdFolders\LapoUssdFiles\LapoMainFiles;

use App\Http\Controllers\Lapo;
use Symfony\Component\Yaml\Yaml;

class PayBills extends Lapo
{

    #########################################################################
    public function payingBills($body)
    {
        $this->initValues($body);

        switch ($this->currentStep) {
            case 3:
                return $this->result("continue", $this->render("billers"));
            case 4:

                if (!in_array($this->lastInputed, ['1', '2'])) {

                    return $this->cancelLastAction("You entered an invalid option. \n" . $this->render("billers"));

                }
                // $this->set_value('biller_id',$this->lastInputed);
                // $billerList = $this->billerList($this->getBillCategories($this->lastInputed));
                // // return $this->result("continue", $this->getBillCategories($this->lastInputed));
                // $count = 0;
                // $return_string = "Select biller: \n";
                // foreach($billerList as $list){
                    
                //     $return_string .= ++$count.'. '.$list->shortName."\n";
                //     $this->set_value($count."-options",(string)$list->billerid);
                //     $this->set_value($count."-inputfield",(string)$list->customerField1);
                    
                // }
                // $return_string .= "#. Next";

                // return $this->result("continue", $return_string);
                
                
                $this->set_value('billcat',$this->lastInputed);
                switch ($this->lastInputed) {
                    case 1:
                        # code... utility
                        $this->set_value('cat-options','utility');
                        return $this->utility($body);
                        // return $this->result("continue", $this->render("utilities"));

                    case 2:
                        # code...   cable
                        $this->set_value('cat-options','cable');
                        return $this->cable($body);
                        // return $this->result("continue", $this->render("cable"));

                    
                    
                }
            default:
                # code...
                switch ($this->get_value('cat-options')) {
                    case 'utility':
                        # code...
                        return $this->utility($body);
                    case 'cable':
                        # code...
                        return $this->cable($body);
                    
                }

            // case 4:
                // $biller_id = $this->get_value('biller_id');

                // if ($this->lastInputed == "#") {
                //     $current_page = $this->get_value('current_page');
                //     if(!isset($current_page)){
                //         $this->set_value('current_page', 12);
                //         $billerList = $this->billerList($this->getBillCategories($biller_id),12);
                //      
                //         if(is_null($billerList)){
                //             $this->backMes("End of list, please select an option");
                //         }
                //         $count = 12;
                //         $return_string= "Select biller: \n";
                //         foreach($billerList as $list){
                            
                //             $return_string .= ++$count.'. '.$list->shortName."\n";
                //             $this->set_value($count."-options",(string)$list->billerid);
                //             $this->set_value($count."-inputfield",(string)$list->customerField1);
                //         }
                //         $return_string .= "#. Next";
    
                //         $this->set_value("selected_biller", $this->lastInputed);
                //         return $this->cancelLastAction($return_string);
                //     }
    
                //     $next = (int)$this->get_value('current_page') + 12;
                //     $billerList = $this->billerList($this->getBillCategories($biller_id),$next);
                //     // $last_data = $this->billerList(NULL,true);
                //     if(is_null($billerList)){
                //         $this->backMes("End of list, please select an option");
                //     }
                //     $count = $next;
                //     $return_string= "Select biller: \n";
                //     // $last_key = array_key_last($lists);
                //     foreach($billerList as $list){
                //        
                //         $return_string .= ++$count.' '.$list->shortName."\n";
                //         $this->set_value($count."-options",(string)$list->billerid);
                //         $this->set_value($count."-inputfield",(string)$list->customerField1);
                        
                //     }
                //     // $return_string .= "#. Next";
                //     // if($lists[$last_key] == $last_data['matches']){
                //     //     str_replace("9. Next","",$return_string); 
                //     // }
    
                //     $this->set_value("selected_biller", $this->lastInputed);
                //     $this->set_value('current_page', (int)$current_page + 12);
                //     return $this->cancelLastAction($return_string);
                // }


                // if (!in_array($this->lastInputed, ['1', '2','3','4','5','7','8','9'])) {

                //     return $this->cancelLastAction("You entered an invalid option.");

                // }
                // $this->set_value("selected_biller", $this->lastInputed);

                // $billitems = $this->billerList($this->getBillItems());
                
                // $count = 0;
                // $return_string = "Select biller item: \n";
                // foreach($billitems as $list){
                    
                //     $return_string .= ++$count.'. '.$list->paymentitemname."\n";
                //     $this->set_value($count.'-paymentCode', (string)$list->paymentCode);
                //     $this->set_value($count.'-paymentAmount', (string)$list->amount);
                //     $this->set_value($count.'-itemid', (string)$list->paymentitemid);
                // }
                // $return_string .= "#. Next";

                // return $this->result("continue", $return_string);
                // return $this->result("continue", $this->getBillItems());




            // case 5:

            //     if ($this->lastInputed == "#") {
            //         $current_page = $this->get_value('current_page');
            //         if(!isset($current_page)){
            //             $this->set_value('current_page', 12);
            //             $billitems = $this->billerList($this->getBillItems(),12);
            //             // var_dump($lists); exit;
            //             if(is_null($billitems)){
            //                 $this->backMes("End of list, please select an option");
            //             }
            //             $count = 12;
            //             $return_string= "Select biller item: \n";
            //             foreach($billitems as $list){
            //                 // var_dump($match);
            //                 $return_string .= ++$count.'. '.$list->paymentitemname."\n";
            //                 $this->set_value($count.'-paymentCode', (string)$list->paymentCode);
            //                 $this->set_value($count.'-paymentAmount', (string)$list->amount);
            //                 $this->set_value($count.'-itemid', (string)$list->paymentitemid);
                            
            //             }
            //             $return_string .= "#. Next";
    
            //             $this->set_value("bill_item", $this->lastInputed);
            //             return $this->cancelLastAction($return_string);
            //         }
    
            //         $next = (int)$this->get_value('current_page') + 12;
            //         $billitems = $this->billerList($this->getBillItems(),12);
            //         // $last_data = $this->billerList(NULL,true);
            //         if(is_null($billitems)){
            //             $this->backMes("End of list, please select an option");
            //         }
            //         $count = $next;
            //         $return_string= "Select biller item: \n";
            //         // $last_key = array_key_last($lists);
            //         foreach($billitems as $list){
            //             // var_dump($match);
            //             $return_string .= ++$count.' '.$list->paymentitemname."\n";
            //             $this->set_value($count.'-paymentCode', (string)$list->paymentCode);
            //             $this->set_value($count.'-paymentAmount', (string)$list->amount);
            //             $this->set_value($count.'-itemid', (string)$list->paymentitemid);
                        
            //         }
            //         // $return_string .= "#. Next";
            //         // if($lists[$last_key] == $last_data['matches']){
            //         //     str_replace("9. Next","",$return_string); 
            //         // }
    
            //         $this->set_value("bill_item", $this->lastInputed);
            //         $this->set_value('current_page', (int)$current_page + 12);
            //         return $this->cancelLastAction($return_string);
            //     }

            //     $this->set_value("bill_item", $this->lastInputed);
            //     $customer_number = $this->get_value($this->get_value("selected_biller")."-inputfield");
            //     return $this->result("continue", $customer_number != null ? $customer_number : $this->render("customer_number"));

            // case 6:

            //     $this->set_value("cust_number", $this->lastInputed);
            //     $biller_amount = $this->get_value($this->get_value('bill_item')."-paymentAmount");
            //     $this->set_value("amount", $biller_amount/100);
            //     return $this->result("continue", $biller_amount != null && (int)$biller_amount > 0 ? "Press 1 to continue.\nAmount: ".$biller_amount/100 : $this->render("transfer_amount"));

            // case 7:
            //     if($this->lastInputed != "1"){
            //         $this->set_value("amount", $this->lastInputed);
            //     }
            //     return $this->getAccount($body);

            // case 8:

            //     $this->set_value("debit_account", $this->lastInputed);
            //     return $this->result("continue", $this->render("pin"));
            
            // case 9:
                
            //     $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
            //     $this->set_value("pin", $this->lastInputed);
            //     $validate = $this->PIN($body);
            //     if (strlen($pin) != 4) {

            //         return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

            //     } elseif ($this->PIN($body) == "Successful") {

            //         //return $this->result("end", "Your request is processing...");
            //         return $this->QueryBill($body);

            //     } else {

            //         return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
            //     }

                
        }

    }

    public function utility($body){
        switch ($this->currentStep) {
            case 4:
                # code...
                return $this->result("continue", $this->render("utilities"));
            case 5:
                //"Select Biller:\n1. IKE Prepaid\n2. IKE Postpaid\n3. EKE Prepaid\n4. EKE Postpaid\n5. Lagos Water Corporation\n6. Waste Management Billing System\n7. BEDC\n8. IBEDC
                switch ($this->lastInputed) {
                    case 1:
                        # code...
                        $this->set_value('utility_item-options','ikeprep');
                       return $this->ikeprep($body);
                    case 2:
                        # code...
                        $this->set_value('utility_item-options','ikepost');
                        return $this->ikepost($body);
                    case 3:
                        # code...
                        $this->set_value('utility_item-options','ekeprep');
                        return $this->ekeprep($body);
                    case 4:
                        # code...
                        $this->set_value('utility_item-options','ekepost');
                        return $this->ekepost($body);
                    case 5:
                        # code...
                        $this->set_value('utility_item-options','waste');
                        return $this->waste($body);
                    case 6:
                        # code...
                        $this->set_value('utility_item-options','bedc');
                        return $this->bedc($body);
                    case 7:
                        # code...
                        $this->set_value('utility_item-options','ibedc');
                        return $this->ibedc($body);
                    

                }
            default:
                switch ($this->get_value("utility_item-options")) {
                    case 'ikeprep':
                        # code...
                        return $this->ikeprep($body);
                    case 'ikepost':
                        # code...
                        return $this->ikepost($body);
                    case 'ekeprep':
                        # code...
                        return $this->ekeprep($body);
                    case 'ekepost':
                        # code...
                        return $this->ekepost($body);
                    case 'waste':
                        # code...
                        return $this->waste($body);
                    case 'bedc':
                        # code...
                        return $this->bedc($body);
                    case 'ibedc':
                        # code...
                        return $this->ibedc($body);
                }

        }
    }

    public function cable($body){
        switch ($this->currentStep) {
            case 4:
                # code...
                return $this->result("continue", $this->render("cable"));
            case 5:
                //Select Biller:\n1. DSTV\n2. GOTV\n3. iROKOtv\n4. Startimes\n5. TSTV
                switch ($this->lastInputed) {
                    case 1:
                        # code...
                        $this->set_value('cable_item-options','dstv');
                        return $this->dstv($body);
                    case 2:
                        # code...
                        $this->set_value('cable_item-options','gotv');
                        return $this->gotv($body);
                    case 3:
                        # code...
                        $this->set_value('cable_item-options','iroko');
                        return $this->iroko($body);
                    case 4:
                        # code...
                        $this->set_value('cable_item-options','startimes');
                        return $this->startimes($body);
                    
                } 
            default:
                switch ($this->get_value("cable_item-options")) {
                    case 'dstv':
                        # code...
                        return $this->dstv($body);
                    case 'gotv':
                        # code...
                        return $this->gotv($body);
                    case 'iroko':
                        # code...
                        return $this->iroko($body);
                    case 'startimes':
                        # code...
                        return $this->startimes($body);

                }

        }
    }

    public function dstv($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                //Select product\n1. Compact\n2. Compact + HD/ExtraView\n3. Compact Plus\n4. Compact Plus + HD/ExtraView\n5. Premium\n6. Premium + XtraView\n00. Back
                // var_dump($this->render("dstv")); exit;
                return $this->result("continue", $this->render("dstv"));
            case 6:
                # code...
                $this->set_value("dstv",$this->lastInputed);
                return $this->result("continue",  $this->render("customer_number"));
            case 7:
                # code...
                $amount = [
                    '1' => "7900",
                    '2' => "10400",
                    '3' => "12400",
                    '4' => "14900",
                    '5' => "18400",
                    '6' => "20900",
                ];

                $paymentCode = [
                    '1' => "10403",
                    '2' => "10409",
                    '3' => "10430",
                    '4' => "10451",
                    '5' => "10401",
                    '6' => "10458",
                ];

                $bill_id = $this->set_value("bill_code","104");

                $this->set_value("cust_number", $this->lastInputed);
                $biller_amount = $amount[$this->get_value("dstv")];
                $paymentCode = $paymentCode[$this->get_value("dstv")];
                $this->set_value("amount", $biller_amount);
                $this->set_value("paymentCode", $paymentCode);
                return $this->result("continue", $biller_amount != null && (int)$biller_amount > 0 ? "Press 1 to continue.\nAmount: ".$biller_amount : $this->render("transfer_amount"));
            case 8:
                if($this->lastInputed != "1"){
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 9:

                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 10:
            
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }

        }
    }

    public function gotv($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                //Select product\n1. GOtv Smallie - monthly\n2. Lite\n3. GOtv Jinja Bouquet"\n4. GOtv Jolli Bouquet\n5. SUPA\n6. GOtv Max\n00. Back
                return $this->result("continue", $this->render("gotv"));
            case 6:
                # code...
                $this->set_value("gotv",$this->lastInputed);
                return $this->result("continue",  $this->render("customer_number"));
            case 7:
                # code...
                $amount = [
                    '1' => "800",
                    '2' => "410",
                    '3' => "1640",
                    '4' => "2460",
                    '5' => "5500",
                    '6' => "3600",
                ];

                $paymentCode = [
                    '1' => "459122",
                    '2' => "459128",
                    '3' => "459120",
                    '4' => "459121",
                    '5' => "459133",
                    '6' => "459119",
                ];

                $bill_id = $this->set_value("bill_code","459");

                $this->set_value("cust_number", $this->lastInputed);
                $biller_amount = $amount[$this->get_value("gotv")];
                $paymentCode = $paymentCode[$this->get_value("gotv")];
                $this->set_value("amount", $biller_amount);
                $this->set_value("paymentCode", $paymentCode);
                return $this->result("continue", $biller_amount != null && (int)$biller_amount > 0 ? "Press 1 to continue.\nAmount: ".$biller_amount : $this->render("transfer_amount"));
            case 8:
                # code...
                if($this->lastInputed != "1"){
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 9:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 10:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function iroko($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                //Select product\n1. iROKOtv Plus Web Access\n00. Back
                return $this->result("continue", $this->render("iroko"));
            case 6:
                # code...
                $this->set_value("iroko",$this->lastInputed);
                return $this->result("continue",  $this->render("customer_number"));
            case 7:
                # code...
                $amount = [
                    '1' => "800"
                ];

                $paymentCode = [
                    '1' => "97701"
                ];

                $bill_id = $this->set_value("bill_code","977");

                $this->set_value("cust_number", $this->lastInputed);
                $biller_amount = $amount[$this->get_value("iroko")];
                $paymentCode = $paymentCode[$this->get_value("iroko")];
                $this->set_value("amount", $biller_amount);
                $this->set_value("paymentCode", $paymentCode);
                return $this->result("continue", $biller_amount != null && (int)$biller_amount > 0 ? "Press 1 to continue.\nAmount: ".$biller_amount : $this->render("transfer_amount"));
            case 8:
                # code...
                if($this->lastInputed != "1"){
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 9:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 10:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function startimes($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                //Select product\n1. Nova - One Month\n2. Basic - One Month\n3. Smart - One Month\n4. Super - One Month\n5. Classic One Month\n6. Solar - Nova\n7. Solar - Smart\n8. Solar - Super\n00. Back
                return $this->result("continue", $this->render("startimes"));
            case 6:
                # code...
                $this->set_value("startimes",$this->lastInputed);
                return $this->result("continue",  $this->render("customer_number"));
            case 7:
                # code...
                $amount = [
                    '1' => "4200",
                    '2' => "1700",
                    '3' => "2200",
                    '4' => "4200",
                    '5' => "2500",
                    '6' => "9900",
                    '7' => "10500",
                    '8' => "11400",
                ];

                $paymentCode = [
                    '1' => "240617930",
                    '2' => "240617933",
                    '3' => "240617936",
                    '4' => "240617945",
                    '5' => "240670497",
                    '6' => "240687622",
                    '7' => "240687623",
                    '8' => "240687624",
                ];

                $bill_id = $this->set_value("bill_code","240");

                $this->set_value("cust_number", $this->lastInputed);
                $biller_amount = $amount[$this->get_value("startimes")];
                $paymentCode = $paymentCode[$this->get_value("startimes")];
                $this->set_value("amount", $biller_amount);
                $this->set_value("paymentCode", $paymentCode);
                return $this->result("continue", $biller_amount != null && (int)$biller_amount > 0 ? "Press 1 to continue.\nAmount: ".$biller_amount : $this->render("transfer_amount"));
            case 8:
                if($this->lastInputed != "1"){
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 9:

                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 10:
            
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }

        }
    }


    public function ikeprep($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","766");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "76601");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function ikepost($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","848");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "84801");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function ekeprep($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","1473");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "04147302");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function ekepost($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","1316");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "04131606");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function waste($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","2306");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "04230602");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function bedc($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","767");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "76701");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }

    public function ibedc($body){
        switch ($this->currentStep) {
            case 5:
                # code...
                return $this->result("continue",  $this->render("customer_number"));
            case 6:
                # code...
                $bill_id = $this->set_value("bill_code","784");

                $this->set_value("cust_number", $this->lastInputed);


                $this->set_value("paymentCode", "78401");
                return $this->result("continue", $this->render("transfer_amount"));
            case 7:
                # code...
                if($this->lastInputed != "1"){
                    if((int)$this->lastInputed > 20000){
                        return $this->result('end',"Limit of N20,000 exceeded.");
                    }
                    $this->set_value("amount", $this->lastInputed);
                }
                return $this->getAccount($body);
            case 8:
                # code...
                $this->set_value("debit_account", $this->lastInputed);
                return $this->result("continue", $this->render("pin"));
            case 9:
                # code...
                $pin = preg_replace("/[^-0-9\.]/", "", $this->lastInputed);
                $this->set_value("pin", $this->lastInputed);
                $validate = $this->PIN($body);
                if (strlen($pin) != 4) {

                    return $this->cancelLastAction("You entered an invalid pin.\n" . $this->render("pin"));

                } elseif ($this->PIN($body) == "Successful") {

                    //return $this->result("end", "Your request is processing...");
                    return $this->QueryBill($body);

                } else {

                    return $this->cancelLastAction("You entered an incorrect pin.\n" . $this->render("pin"));
                    
                }
        }
    }
    
    public function getBillCategories($biller_id)
    {
       
        switch (strval($biller_id)) {
            //\n1. Utilities\n2. Cable TV\n3. Government Payments\n4. Betting, Lottery and Gaming\n5. Lagos State CBS
            case '1':
                $id = 1;
                break;
            case '2':
                $id = 2;
                break;
            case '3':
                $id = 6;
                break;
            case '4':
                $id = 41;
                break;
            case '5':
                $id = 62;
                break;                  
            default:
                break;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140/qt-integration/public/api/get-biller-in-category?category_id=$id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {

            return "Unable to fecth billers, please try again later.";
            
        }

        $billers = json_decode($response);
        $count = 1;
        $display = [];

        return $billers;
        // return [
        //     'billerid'=>$biller->billerid,
        //     'billername'=>$biller->billername,
        //     'shortName'=>$biller->shortName
        // ];

        foreach ($billers as $biller) {
            
            array_push($display, strval($count) .'. '. $biller->billername);
            $this->set_value(strval($count).'-option', $biller->billerid);
            $this->set_value(strval($count).'-inputField', $biller->customerField1);
            $count++;

        }

        if ($this->lastInputed == '0') {

            return (implode("\n", array_values(array_slice($display, 9))));

        } else {

            return (implode("\n", array_values(array_slice($display, 0, 9))));

        }
    }

    public function billerList($array, $offset = NULL, $limit = NULL){

        try {

            $data = array_slice($array,0,12);
            
            if(!is_null($offset)){
                $data = array_slice($array,$offset,12);
            }
            // var_dump($data); exit;
            if(is_null($data)){
                return NULL;
            }
            return $data;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getBillItems()
    {
        $id = $this->get_value($this->lastInputed."-options");
    //    var_dump($id); exit;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140/qt-integration/public/api/get-billitem?biller_id=$id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {

            return "Unable to fecth bills, please try again later.";
            
        }

        $billers = json_decode($response);
        $count = 1;
        $display = [];

        return $billers;
        foreach ($billers as $biller) {
            array_push($display, strval($count) .'. '. $biller->paymentitemname);
            $this->set_value(strval($count).'-paymentCode', (string)$biller->paymentCode);
            $this->set_value(strval($count).'-paymentAmount', (string)$biller->amount);
            $count++;
        }

        if ($this->lastInputed == '0') {

            return (implode("\n", array_values(array_slice($display, 9))));

        } else {

            return (implode("\n", array_values(array_slice($display, 0, 9))));

        }
    }

    

    public function getAccount($body)
    {
        $curl = curl_init();
        $msisdn = '0' . substr($this->posted["msisdn"], -10);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/accounts-by-phone?phone=$msisdn",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $yummy = json_decode($response);

        if ($err) {
            $this->result("end", "An error occured. Please try again after some time.");
        }

        if ($yummy->code != 0) {

            return $this->result("end", "Unable to fetch account\n" . $yummy->message);

        }

        $count = 1;
        $arr = [];
        foreach ($yummy->items as $account) {
            array_push($arr, $count . ". " . $account->ACCOUNT_NUMBER . " ");
            $this->set_value($count, $account->ACCOUNT_NUMBER);
            $count++;
        }

        $res = implode("\n", array_values($arr));
        return $this->result("continue", "Select Account:\n$res");

    }


    public function PIN($body)
    {

        $pin = $this->get_value("pin");
        $msisdn = '0' . substr($this->posted["msisdn"], -10);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://10.0.0.140:8080/ords/pcash/fcubs/auth?phone=$msisdn&pin=$pin",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
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

    public function QueryBill($body)
    {
        $msisdn = '0' . substr($this->posted["msisdn"], -10);

        $account_selected = $this->get_value("debit_account");
        $debit_account = $this->get_value($account_selected);
        $amount = $this->get_value("amount");
        $bill_id = $this->get_value("bill_code");
        // $bill_id = $this->get_value($billing_code);
        // $bill_id = $this->get_value($this->get_value("bill_item")."-itemid");
        // $paymentCode = $this->get_value($this->get_value('bill_item')."-paymentCode");
        $paymentCode = $this->get_value("paymentCode");
        // $bill_id = $this->get_value($this->get_value("selected_biller")."-options");
        $pin = $this->get_value("pin");
        // $customer_no = $this->get_value("customer_no");
        $customer_no = $this->get_value("cust_number");
        

        $curl = curl_init();
        // echo "{'phone':'$msisdn','pin':'$pin','customer_number':'$customer_no','bill_id':$bill_id,'debit_account':'$debit_account','amount':'$amount','paymentCode':$paymentCode}"; exit;
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.0.0.140/ords/pcash/fcubs/bill-payment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "{\"phone\":\"$msisdn\",\"pin\":\"$pin\",\"customer_number\":\"$customer_no\",\"bill_id\":$bill_id,\"debit_account\":\"$debit_account\",\"amount\":\"$amount\",\"paymentCode\":$paymentCode}",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: cookiesession1=0CCA0A423TEZAWDDWAJUNGDDGURH5183',
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $this->result("end", "an error occured\n Please try again");
        } elseif ($response) {

            $request = json_decode($response);
            if(isset($request->message) && !empty($request->message)){
                $description = $request->message;  
                return $this->result("End", "LAPO Swift Banking\n$description");
            }elseif(isset($request->description) && !empty($request->description)){
                $description = $request->description;  
                return $this->result("End", "LAPO Swift Banking\n$description");
            }else{
                return $this->result("End", "Something went wrong\n Please try again");
            }

        } else {
            return $this->result("end", "Service temporarily unavailable\nPlease try again");
        }
    }

##################################################################################################
    public function render($key)
    {
        $arr = Yaml::parse(file_get_contents("/Users/mac/Development/ussd/ussed_laravel/app/UssdFolders/LapoUssdFiles/config.yml"));
        return $arr['pages'][$key];
    }
}
