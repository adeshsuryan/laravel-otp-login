<?php

namespace adeshsuryan\LaravelOTPLogin\Controllers;

use adeshsuryan\LaravelOTPLogin\OneTimePassword;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class OtpController extends Controller
{
    public function view(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route(config('otp.login_route', 'login'))
                ->withErrors(['email' => __('laravel-otp-login::messages.unauthorized')]);
        }

        $otp = OneTimePassword::query()
            ->where('user_id', Auth::id())
            ->where('status', 'waiting')
            ->orderByDesc('id')
            ->first();

        if (! $otp instanceof OneTimePassword || $otp->isExpired()) {
            Auth::logout();

            return redirect()->route(config('otp.login_route', 'login'))
                ->withErrors(['email' => __('laravel-otp-login::messages.otp_expired')]);
        }

        return view('laravel-otp-login::otpvalidate');
    }

    public function check(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route(config('otp.login_route', 'login'));
        }

        $code = $request->input('code');
        if ($code === null || $code === '') {
            return redirect()->route('otp.view')
                ->withErrors(['code' => __('laravel-otp-login::messages.code_missing')]);
        }

        $user = Auth::user();
        $otp = OneTimePassword::query()
            ->where('user_id', $user->{config('otp.user_id_field', 'id')})
            ->where('status', 'waiting')
            ->orderByDesc('id')
            ->first();

        if (! $otp instanceof OneTimePassword || $otp->isExpired()) {
            Auth::logout();

            return redirect()->route(config('otp.login_route', 'login'))
                ->withErrors(['email' => __('laravel-otp-login::messages.otp_expired')]);
        }

        if (! $otp->checkPassword((string) $code)) {
            return redirect()->route('otp.view')
                ->withErrors(['code' => __('laravel-otp-login::messages.code_mismatch')]);
        }

        $otp->acceptEntrance($user);

        $cookieName = config('otp.verified_cookie_name', 'otp_login_verified');
        $minutes = (int) config('otp.verified_cookie_minutes', 60 * 24 * 30);
        $value = 'user_id_'.$user->{config('otp.user_id_field', 'id')};

        Cookie::queue(cookie(
            $cookieName,
            $value,
            $minutes,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));

        return redirect()->intended(config('otp.after_login_redirect', '/home'));
    }
}
