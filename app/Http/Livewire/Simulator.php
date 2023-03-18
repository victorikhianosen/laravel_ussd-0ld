<?php

namespace App\Http\Livewire;


use App\Http\Controllers\StartUssd;
use Livewire\Component;


class Simulator extends Component
{

    public $msisdn;
    public $commandID='111';
    public $content;
    public $src;
    public $serviceCode;
    public $result;

    public $count=1;

    public $selectedOption='Choose Ussd Service';
    public $showDropdown = false;
    public $dropdownName;
    public $dropdownData=['Lapo','1xBet'];
    public $className;
    public $key;

    protected $timers;

    protected $rules=[
        'content'=>'max:12|min:0',
        'msisdn'=>'string|required|nigeria_phone', 
    ];

    public $timeLeft = 60;
   


    public function updated($propertyName){
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.simulator');
    }


    public function submitValue()
    {

        try {

            $data = ['foo' => 'bar'];
        $this->emit('dataReceived', $data);
            $validate = $this->validate();
            if(isset($validate)){

            // when the session is just starting
            if($this->count>1){
                $this->commandID='112';
            }
           // Perform your logic here to process the data
        $this->result = [
            'msisdn' => $this->msisdn,
            'commandID' => $this->commandID,
            'content' => $this->content,
            'src' => 'mtn',
            'serviceCode' => $this->serviceCode,
        ];
        // dd($this->result);
        
        $ussdInstance =new StartUssd();
        $response =$ussdInstance->index($this->result);
        $this->count=$this->count+1;
        $this->content=1;
        $this->result= $response['reply'];
            }

       
        } catch (\Throwable $th) {
            dd($th);
        }
       
        
       
        
    }

 
  
    public $dropdownKey;
    public $model;
 public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
        $this->count=1;
 
    }
   public function setOption($option)
    {
        $this->selectedOption = $option;
        $this->showDropdown = false;
        // when the session is just starting
       if($this->count==1){
        if($option==$this->dropdownData[0]){
            $this->content="*371#";
            $this->serviceCode='371';
         }
        else if($option==$this->dropdownData[1]){
         $this->content="*6069#"; 
         $this->serviceCode='6069';
         }
          $this->count=1;
       }
        // for continuing session 
       else{
        if($option==$this->dropdownData[0]){
                $this->serviceCode='371';
         }
        else if($option==$this->dropdownData[1]){
             $this->serviceCode='6069';
         }
       }
    }
   function isTwoDimensionalArray($array) {
        // Check if $myArray1D is a 1D or 2D array
        if (is_array($array)) {
            if (count($array) == count($array, COUNT_RECURSIVE)) {
                // echo "1D array";
                return false;
            } else  {
                // echo "2D array";
                return true;
            }
            
        }
        return false;
    }
}
