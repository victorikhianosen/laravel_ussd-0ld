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
        @livewire('simulator')
      </div>
      </div>
      {{-- live wire script --}}
        @livewireScripts
    </body>
      
      @yield('javascript')
  
</html>
