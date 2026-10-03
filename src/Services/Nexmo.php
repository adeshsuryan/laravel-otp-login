<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\ServiceInterface;

class Nexmo implements ServiceInterface
{
    public function sendOneTimePassword($user, $otp, $ref)
    {
        $phoneColumn = config('otp.user_phone_field');
        $userPhone = data_get($user, $phoneColumn, false);
        if (! $userPhone) {
            return false;
        }

        $apiKey = config('otp.services.nexmo.api_key', '');
        $apiSecret = config('otp.services.nexmo.api_secret', '');
        $from = config('otp.services.nexmo.from', '');
        $message = __('laravel-otp-login::messages.otp_message', ['password' => $otp]);

        try {
            $url = 'https://rest.nexmo.com/sms/json?'.http_build_query([
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'to' => $userPhone,
                'from' => $from,
                'text' => $message,
            ]);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $decoded = json_decode((string) $response, true);

            return isset($decoded['messages'][0]['status']) && (string) $decoded['messages'][0]['status'] === '0';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
