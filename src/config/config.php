<?php

return [
    'otp_service_enabled' => env('OTP_SERVICE_ENABLED', true),

    // Prefer log/array locally. Use twilio/nexmo/msg91/biotekno/mail in production as needed.
    'otp_default_service' => env('OTP_SERVICE', 'log'),

    'services' => [
        'array' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\ArrayChannel::class,
        ],
        'log' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\LogChannel::class,
        ],
        'mail' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\MailChannel::class,
            'mailer' => env('OTP_MAILER', null),
        ],
        'biotekno' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\BioTekno::class,
            'username' => env('OTP_USERNAME', null),
            'password' => env('OTP_PASSWORD', null),
            'transmission_id' => env('OTP_TRANSMISSION_ID', null),
        ],
        'nexmo' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\Nexmo::class,
            'api_key' => env('OTP_API_KEY', null),
            'api_secret' => env('OTP_API_SECRET', null),
            'from' => env('OTP_FROM', null),
        ],
        'twilio' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\Twilio::class,
            'account_sid' => env('OTP_ACCOUNT_SID', null),
            'auth_token' => env('OTP_AUTH_TOKEN', null),
            'from' => env('OTP_FROM', null),
        ],
        'msg91' => [
            'class' => \adeshsuryan\LaravelOTPLogin\Services\Msg91::class,
            'api_key' => env('OTP_API_KEY', null),
            'sender' => env('OTP_API_SENDER', null),
            'route' => env('OTP_ROUTE', '4'),
            'country' => env('OTP_COUNTRY', '0'),
        ],
    ],

    'user_model' => env('OTP_USER_MODEL', null),
    'user_table' => env('OTP_USER_TABLE', 'users'),
    'user_id_field' => env('OTP_USER_ID_FIELD', 'id'),
    'user_phone_field' => env('OTP_USER_PHONE_FIELD', 'phone'),
    'user_email_field' => env('OTP_USER_EMAIL_FIELD', 'email'),
    'user_dialing_code_field' => env('OTP_USER_DIALING_CODE_FIELD', 'dialingcode'),

    'otp_reference_number_length' => (int) env('OTP_REFERENCE_NUMBER_LENGTH', 6),
    'otp_timeout' => (int) env('OTP_TIMEOUT', 600),
    'otp_digit_length' => (int) env('OTP_DIGIT_LENGTH', 6),
    'encode_password' => (bool) env('OTP_ENCODE_PASSWORD', false),

    'verified_cookie_name' => env('OTP_VERIFIED_COOKIE', 'otp_login_verified'),
    'verified_cookie_minutes' => (int) env('OTP_VERIFIED_COOKIE_MINUTES', 60 * 24 * 30),

    'after_login_redirect' => env('OTP_AFTER_LOGIN_REDIRECT', '/home'),
    'login_route' => env('OTP_LOGIN_ROUTE', 'login'),
];
