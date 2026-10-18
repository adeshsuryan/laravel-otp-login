<?php

namespace adeshsuryan\LaravelOTPLogin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Server-side trusted-device record backing the "verified" cookie.
 * Only a SHA-256 hash of the random token is stored — the raw token lives
 * exclusively in the (encrypted) cookie, so neither the database nor a
 * predictable user id alone is enough to satisfy the middleware.
 */
class TrustedDevice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('otp.trusted_devices_table', 'otp_trusted_devices');
    }

    /**
     * Issue a new trusted-device token for the user.
     * Returns the cookie value "user_id|token" — raw token is never persisted.
     */
    /**
     * @param  int|string  $userId
     * @param  string|null  $agent
     * @return string "user_id|token"
     */
    public static function issue($userId, int $minutes, $agent = null)
    {
        $token = Str::random(64);

        static::create([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'agent_hash' => $agent !== null ? hash('sha256', $agent) : null,
            'expires_at' => now()->addMinutes($minutes),
        ]);

        return $userId.'|'.$token;
    }

    /**
     * Validate a "user_id|token" cookie value against stored hashes.
     */
    /**
     * @param  int|string|null  $userId
     */
    public static function isTrusted(?string $cookieValue, $userId = null): bool
    {
        if (! is_string($cookieValue) || strpos($cookieValue, '|') === false) {
            return false;
        }

        [$uid, $token] = explode('|', $cookieValue, 2);
        if ($uid === '' || $token === '') {
            return false;
        }
        if ($userId !== null && (string) $uid !== (string) $userId) {
            return false;
        }

        return static::query()
            ->where('user_id', $uid)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** Remove expired rows. */
    public static function pruneExpired(): int
    {
        return static::query()->where('expires_at', '<=', now())->delete();
    }
}
