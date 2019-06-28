<?php

namespace adeshsuryan\LaravelOTPLogin;

use adeshsuryan\LaravelOTPLogin\LoginMiddleware;

/**
 * Class OTPServiceProvider
 * @package adeshsuryan\LaravelOTPLogin
 */
class OTPServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/routes.php');
//        $this->loadViewsFrom(__DIR__ . '/views', 'laravel-otp-login');
        $this->loadTranslationsFrom(__DIR__ . '/translations', 'laravel-otp-login');

        $this->publishes([__DIR__ . '/config/config.php' => config_path('otp.php')], "config");
//        $this->publishes([__DIR__ . '/views' => resource_path('views/vendor/laravel-otp-login')], "views");
        $this->publishes([__DIR__ . '/migrations' => database_path('migrations')], "migrations");
        $this->publishes([__DIR__ . '/services' => app_path('OtpServices')], "migrations");
        $this->publishes([__DIR__ . '/translations' => resource_path('lang/vendor/laravel-otp-login')]);

        $this->app['router']->pushMiddlewareToGroup('web', LoginMiddleware::class);

    }
}
