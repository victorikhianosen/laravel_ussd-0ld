<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
          // to validate nigeria number
          Validator::extend('nigeria_phone', function ($attribute, $value, $parameters, $validator) {
            $pattern='/(^(0)(70|71|80|81|90|91)\d{8})$/';
            return preg_match($pattern,$value);
       
        });
    }
}
