<?php

Route::group(['middleware' => ['before' => 'jwt']],function () {
    Route::get('/login/verify', 'adeshsuryan\LaravelOTPLogin\Controllers\OtpController@view');
    Route::post('/login/check', 'adeshsuryan\LaravelOTPLogin\Controllers\OtpController@check' );
});
