<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\ServiceInterface;

class Msg91 implements ServiceInterface
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
            $url = 'https://control.msg91.com/api/sendhttp.php?'.http_build_query([
                'authkey' => config('otp.services.msg91.api_key', ''),
                'route' => config('otp.services.msg91.route', '4'),
                'country' => config('otp.services.msg91.country', '0'),
                'mobiles' => $ref ?: $userPhone,
                'sender' => config('otp.services.msg91.sender', ''),
                'response' => 'json',
                'message' => $message,
            ]);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $decoded = json_decode((string) $response, true);

            return isset($decoded['type']) && $decoded['type'] === 'success';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
