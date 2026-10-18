<?php

namespace adeshsuryan\LaravelOTPLogin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginMiddleware
{
    /**
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! config('otp.otp_service_enabled', true)) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();
        $skip = ['otp.view', 'otp.verify', 'login', 'logout', 'register', 'password.request', 'password.reset'];
        if ($routeName && in_array($routeName, $skip, true)) {
            return $next($request);
        }

        $user = Auth::user();
        $userId = $user->{config('otp.user_id_field', 'id')};
        $cookieName = config('otp.verified_cookie_name', 'otp_login_verified');

        if (TrustedDevice::isTrusted($request->cookie($cookieName), $userId)) {
            return $next($request);
        }

        $otp = OneTimePassword::query()
            ->where('user_id', $userId)
            ->where('status', 'verified')
            ->orderByDesc('id')
            ->first();

        if ($otp instanceof OneTimePassword && ! $otp->isExpired()) {
            return $next($request);
        }

        $waiting = OneTimePassword::query()
            ->where('user_id', $userId)
            ->where('status', 'waiting')
            ->orderByDesc('id')
            ->first();

        if ($waiting instanceof OneTimePassword && ! $waiting->isExpired()) {
            return redirect()->route('otp.view');
        }

        // Legacy migration path for hosts that intentionally admit users who
        // never received an OTP. Off by default: an auth gate must fail closed.
        if (config('otp.fail_open', false)) {
            return $next($request);
        }

        $row = $waiting instanceof OneTimePassword
            ? $waiting
            : OneTimePassword::firstOrCreate(
                ['user_id' => $userId],
                ['status' => 'waiting']
            );

        $row->send($user);

        return redirect()->route('otp.view');
    }
}
