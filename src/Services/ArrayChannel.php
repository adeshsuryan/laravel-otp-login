<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\ServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ArrayChannel implements ServiceInterface
{
    public const CACHE_KEY = 'laravel_otp_login.last';

    /**
     * @param  Authenticatable|Model  $user
     * @param  string|int  $otp
     */
    public function sendOneTimePassword($user, $otp, $ref)
    {
        $payload = [
            'otp' => (string) $otp,
            'ref' => (string) $ref,
            'user_id' => data_get($user, config('otp.user_id_field', 'id')),
            'sent_at' => now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_KEY, $payload, now()->addMinutes(30));
        Cache::put(self::CACHE_KEY.'.'.$payload['user_id'], $payload, now()->addMinutes(30));

        return true;
    }

    /**
     * @param  int|string|null  $userId
     * @return array{otp: string, ref: string, user_id: mixed, sent_at: string}|null
     */
    public static function last($userId = null)
    {
        if ($userId !== null) {
            return Cache::get(self::CACHE_KEY.'.'.$userId);
        }

        return Cache::get(self::CACHE_KEY);
    }
}
