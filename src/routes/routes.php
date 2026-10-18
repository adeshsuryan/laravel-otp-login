<?php

use adeshsuryan\LaravelOTPLogin\Controllers\OtpController;
use Illuminate\Support\Facades\Route;

$verifyMiddleware = ['web', 'auth'];

$throttle = config('otp.otp_verify_throttle', '10,1');
if ($throttle) {
    $verifyMiddleware[] = 'throttle:'.$throttle;
}

Route::middleware(['web', 'auth'])
    ->group(function () use ($verifyMiddleware) {
        Route::get('/login/verify', [OtpController::class, 'view'])->name('otp.view');
        Route::post('/login/check', [OtpController::class, 'check'])
            ->middleware($verifyMiddleware)
            ->name('otp.verify');
    });
