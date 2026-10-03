<?php

use adeshsuryan\LaravelOTPLogin\Controllers\OtpController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->group(function () {
        Route::get('/login/verify', [OtpController::class, 'view'])->name('otp.view');
        Route::post('/login/check', [OtpController::class, 'check'])->name('otp.verify');
    });
