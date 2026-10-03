<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('laravel-otp-login::messages.verify_phone_title') }}</title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; background: #f4f1ea; color: #1a1a1a; margin: 0; }
        .wrap { max-width: 28rem; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #ddd; }
        h1 { font-size: 1.35rem; margin: 0 0 1rem; }
        label { display: block; margin-bottom: .35rem; }
        input { width: 100%; padding: .6rem; box-sizing: border-box; font-size: 1.1rem; letter-spacing: .1em; }
        button { margin-top: 1rem; padding: .65rem 1.2rem; background: #1f4e79; color: #fff; border: 0; cursor: pointer; }
        .error { color: #8b1e1e; margin: .75rem 0 0; }
        .hint { color: #555; margin-bottom: 1rem; }
        a { color: #1f4e79; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ __('laravel-otp-login::messages.verify_phone_title') }}</h1>
    <p class="hint">{{ __('laravel-otp-login::messages.hero_text', ['digit' => config('otp.otp_digit_length', 6)]) }}</p>
    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <label for="code">{{ __('laravel-otp-login::messages.one_time_password') }}</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus value="{{ old('code') }}">
        @if ($errors->has('code'))
            <p class="error">{{ $errors->first('code') }}</p>
        @endif
        <button type="submit">{{ __('laravel-otp-login::messages.verify_phone_button') }}</button>
    </form>
    <p style="margin-top:1.5rem">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            {{ __('laravel-otp-login::messages.otp_not_received') }}
        </a>
    </p>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
</div>
</body>
</html>
