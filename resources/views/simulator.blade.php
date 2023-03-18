<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1 maximum-scale=1.0, user-scalable=0">
        <link rel="icon" type="image/svg" sizes="16x16" href="{{ asset('assets/page_icon.svg') }}">
        <link rel="icon" type="image/svg" sizes="32x32" href="{{ asset('assets/page_icon_small.svg') }}">
        <title> @yield('title')</title>

        <!-- Fonts -->
        <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>

        <!-- Styles -->
    
        <link rel="stylesheet" type="text/css" href="{{ asset('css/main.css') }}" >
        @yield('css_file')

        {{-- live wire style--}}
        @livewireStyles
    </head>
    <body >
      
      <div class="main_content">
        <h1>Session Time Left: <span id="time-left">60</span> seconds</h1>
        <div id="timer-expired" style="display: none;">
            <p>Session is Over!</p>
        </div>
        @livewire('simulator')
      </div>
      </div>
      {{-- live wire script --}}
        @livewireScripts
    </body>
      <script>
         let timeLeft = 60;
        let timer;
        function resetTimer() {
            clearInterval(timer);
            timeLeft = 60;
            updateTimerDisplay();
        }
       

        function startTimer() {
            timer = setInterval(function() {
                timeLeft--;
                updateTimerDisplay();
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    document.getElementById('time-left').style.display = 'none';
                    document.getElementById('timer-expired').style.display = 'block';
                }
            }, 1000);
        }

function updateTimerDisplay() {
  document.getElementById('time-left').textContent = timeLeft;
}
          Livewire.on('dataReceived', function(data) {
         resetTimer();
         startTimer();
        });

     

      </script>
  
</html>
