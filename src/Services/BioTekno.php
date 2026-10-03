<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\ServiceInterface;

class BioTekno implements ServiceInterface
{
    public function sendOneTimePassword($user, $otp, $ref)
    {
        $phoneColumn = config('otp.user_phone_field');
        $userPhone = data_get($user, $phoneColumn, false);
        if (! $userPhone) {
            return false;
        }

        $message = __('laravel-otp-login::messages.otp_message', ['password' => $otp]);

        try {
            $url = 'https://processor.smsorigin.com/xml/process.aspx?'.http_build_query([
                'username' => config('otp.services.biotekno.username'),
                'password' => config('otp.services.biotekno.password'),
                'transmissionId' => config('otp.services.biotekno.transmission_id'),
                'numbers' => $userPhone,
                'message' => $message,
            ]);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            return is_string($response) && $response !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
