<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\ServiceInterface;

class Twilio implements ServiceInterface
{
    public function sendOneTimePassword($user, $otp, $ref)
    {
        $phoneColumn = config('otp.user_phone_field');
        $userPhone = data_get($user, $phoneColumn, false);
        if (! $userPhone) {
            return false;
        }

        $from = config('otp.services.twilio.from', '');
        $sid = config('otp.services.twilio.account_sid', '');
        $token = config('otp.services.twilio.auth_token', '');
        $message = __('laravel-otp-login::messages.otp_message', ['password' => $otp]);

        try {
            $url = 'https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'Body' => $message,
                'From' => $from,
                'To' => $userPhone,
            ]);
            curl_setopt($ch, CURLOPT_USERPWD, $sid.':'.$token);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            curl_close($ch);

            return is_string($response) && strpos($response, '"status":') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
