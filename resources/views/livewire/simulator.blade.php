<div>
   
        @csrf
        <div class="dropdown">
            <button wire:model="$model"  wire:click="toggleDropdown" class="dropdown-toggle"> 
              <p class="text"> {{  $selectedOption }}</p>
              @if ($showDropdown)
              <img class="dropdown_arrow" src="{{ asset('assets/down_arrow.svg') }}" alt="">
              @else
            
              <img class="dropdown_arrow" src="{{ asset('assets/up_arrow.svg') }}" alt="">
              @endif
            
             
             </button>
            <div wire:model="model" wire:key="dropdown-menu" class="dropdown-menu  {{ $showDropdown ? 'show' : '' }}" wire:show="showDropdown">
          
             @if ($this->isTwoDimensionalArray($dropdownData)==true)
             @forelse ($dropdownData as $data)
             <p wire:click="setOption('{{$data[$key]}}')" class="dropdown-item">{{$data[$key]}}</p>   
             @empty
             <p class="text"> {{  $selectedOption }}</p>
             @endforelse
             @else
             @for ($i=0; $i<count($dropdownData); $i++)
             <p wire:click="setOption('{{$dropdownData[$i]}}')" class="dropdown-item">{{$dropdownData[$i]}}</p>    
             @endfor  
             @endif
            </div>
          </div>

        <div>
            <label for="msisdn">MSISDN:</label>
            <input type="text" id="msisdn" wire:model="msisdn">
        </div>
        @error('msisdn')
        <span class="error">{{ $message }}</span> 
        @enderror

       @if ($count==1)    
       @else
       <div>
        <label for="content">Response:</label>
        <input type="text" id="content" wire:model="content">
        </div>
        @error('content')
        <p class="error">{{ $message }}</p> 
        @enderror
       @endif

        <button wire:click="submitValue" class="button">Submit</button>
    

    <div>
        <h2>Result:</h2>
    
        <pre>{{ $result}}</pre>
        
       
    </div>
</div>

