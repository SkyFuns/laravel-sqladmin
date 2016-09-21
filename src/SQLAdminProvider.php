<?php

namespace Upstriving\Curd;

use Illuminate\Support\ServiceProvider;

class SQLAdminProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sqladmin', function(){
            return new SQLAdmin();
        });
    }
}