<?php

namespace adeshsuryan\LaravelOTPLogin\Tests;

use adeshsuryan\LaravelOTPLogin\OTPServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [OTPServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('otp.otp_default_service', 'array');
        $app['config']->set('otp.otp_timeout', 600);
        $app['config']->set('otp.user_table', 'users');
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('dialingcode')->nullable();
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../src/migrations');
    }
}
