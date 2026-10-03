<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\ServiceInterface;
use Illuminate\Support\Facades\Log;

class LogChannel implements ServiceInterface
{
    public function sendOneTimePassword($user, $otp, $ref)
    {
        Log::info('laravel-otp-login OTP issued', [
            'user_id' => data_get($user, config('otp.user_id_field', 'id')),
            'ref' => $ref,
            'otp' => $otp,
        ]);

        // Also stash for test harnesses that read the array channel API.
        (new ArrayChannel())->sendOneTimePassword($user, $otp, $ref);

        return true;
    }
}
