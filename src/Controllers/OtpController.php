<?php

namespace adeshsuryan\LaravelOTPLogin\Controllers;

use adeshsuryan\LaravelOTPLogin\OneTimePassword;
use adeshsuryan\LaravelOTPLogin\TrustedDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class OtpController extends Controller
{
    /**
     * @return View|RedirectResponse
     */
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

    /**
     * @return RedirectResponse|JsonResponse
     */
    public function check(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route(config('otp.login_route', 'login'));
        }

        $validated = $request->validate([
            'code' => 'required|string|digits_between:4,9',
        ]);
        $code = $validated['code'];

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
            if ($otp->recordFailedAttempt()) {
                Auth::logout();

                return redirect()->route(config('otp.login_route', 'login'))
                    ->withErrors(['email' => __('laravel-otp-login::messages.too_many_attempts')]);
            }

            return redirect()->route('otp.view')
                ->withErrors(['code' => __('laravel-otp-login::messages.code_mismatch')]);
        }

        $otp->acceptEntrance($user);

        $cookieName = config('otp.verified_cookie_name', 'otp_login_verified');
        $minutes = (int) config('otp.verified_cookie_minutes', 60 * 24 * 30);
        $userId = $user->{config('otp.user_id_field', 'id')};
        $value = TrustedDevice::issue($userId, $minutes, $request->userAgent());

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
