<?php

namespace adeshsuryan\LaravelOTPLogin;

use adeshsuryan\LaravelOTPLogin\Console\PruneCommand;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class OTPServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'otp');
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'laravel-otp-login');
        $this->loadTranslationsFrom(__DIR__.'/translations', 'laravel-otp-login');

        $this->publishes([
            __DIR__.'/config/config.php' => config_path('otp.php'),
        ], 'otp-config');

        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/laravel-otp-login'),
        ], 'otp-views');

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations'),
        ], 'otp-migrations');

        $langPath = method_exists($this->app, 'langPath')
            ? $this->app->langPath('vendor/laravel-otp-login')
            : resource_path('lang/vendor/laravel-otp-login');

        $this->publishes([
            __DIR__.'/translations' => $langPath,
        ], 'otp-translations');

        /** @var Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('otp.verified', LoginMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneCommand::class]);
        }
    }
}
