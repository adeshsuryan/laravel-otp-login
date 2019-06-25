<?php
return [
    'otp_service_enabled' => true,
    'otp_default_service' => env("OTP_SERVICE", "nexmo"),
    'services' => [
        'biotekno' => [
            "class" => \adeshsuryan\LaravelOTPLogin\Services\BioTekno::class,
            "username" => env('OTP_USERNAME', null),
            "password" => env('OTP_PASSWORD', null),
            "transmission_id" => env('OTP_TRANSMISSION_ID', null)
        ],
        'nexmo' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\Nexmo::class,
            'api_key' => env("OTP_API_KEY", null),
            'api_secret' => env('OTP_API_SECRET', null),
            'from' => env('OTP_FROM', null)
        ],
        'twilio' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\Twilio::class,
            'account_sid' => env("OTP_ACCOUNT_SID", null),
            'auth_token' => env("OTP_AUTH_TOKEN", null),
            'from' => env("OTP_FROM", null)
        ],
        'msg91' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\Msg91::class,
            'api_key' => env("OTP_API_KEY", null),
            'sender' => env('OTP_API_SENDER',null),
            'route' => env('OTP_ROUTE', '4'),
            'country' => env('OTP_COUNTRY', '0'),
        ]
    ],
    'user_phone_field' => 'phone',
    'otp_reference_number_length' => 6,
    'otp_timeout' => 7890000,
    'otp_digit_length' => 6,
    'encode_password' => false
];
