<?php

namespace adeshsuryan\LaravelOTPLogin;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OneTimePassword extends Model
{
    protected $table = 'one_time_passwords';

    protected $fillable = ['user_id', 'status'];

    /** @var Authenticatable|null */
    protected $otpUser;

    /**
     * @return HasMany<OneTimePasswordLog, $this>
     */
    public function oneTimePasswordLogs()
    {
        return $this->hasMany(OneTimePasswordLog::class, 'user_id', 'user_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user()
    {
        /** @var class-string<Model> $model */
        $model = config('otp.user_model') ?: $this->guessUserModel();

        return $this->belongsTo($model, 'user_id', config('otp.user_id_field', 'id'));
    }

    /**
     * @param  Authenticatable  $user
     * @return array{otp: string, status: bool|null}|null
     */
    public function send($user)
    {
        $this->otpUser = $user;

        $dialField = config('otp.user_dialing_code_field', 'dialingcode');
        $phoneField = config('otp.user_phone_field', 'phone');
        $dial = (string) data_get($user, $dialField, '');
        $phone = (string) data_get($user, $phoneField, '');
        $ref = ltrim($dial.$phone, '+');

        $otp = $this->createOTP($ref);
        if ($otp === null || $otp === false || $otp === '') {
            return null;
        }

        $payload = ['otp' => $otp, 'status' => true];

        if (config('otp.otp_service_enabled', true)) {
            $payload['status'] = $this->sendOTPWithService($user, $otp, $ref);
        }

        return $payload;
    }

    /**
     * @param  Authenticatable  $user
     * @param  string  $otp
     * @param  string  $ref
     * @return bool
     */
    private function sendOTPWithService($user, $otp, $ref)
    {
        $factory = new ServiceFactory;
        $service = $factory->getService(config('otp.otp_default_service'));

        if ($service) {
            return (bool) $service->sendOneTimePassword($user, $otp, $ref);
        }

        return false;
    }

    /**
     * @param  string  $ref
     * @return string|null
     */
    public function createOTP($ref)
    {
        $this->discardOldPasswords();
        $otp = $this->OTPGenerator();
        $otpCode = $otp;

        if (config('otp.encode_password', false)) {
            $otpCode = Hash::make($otp);
        }

        $this->update(['status' => 'waiting']);

        $this->oneTimePasswordLogs()->create([
            'user_id' => $this->otpUser->{config('otp.user_id_field', 'id')},
            'otp_code' => $otpCode,
            'refer_number' => $ref,
            'status' => 'waiting',
        ]);

        return $otp;
    }

    private function OTPGenerator(): string
    {
        $length = max(4, min(9, (int) config('otp.otp_digit_length', 6)));
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);

        return str_pad((string) random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }

    public function discardOldPasswords(): int
    {
        $this->update(['status' => 'discarded']);

        return $this->oneTimePasswordLogs()
            ->whereIn('status', ['waiting', 'verified'])
            ->update(['status' => 'discarded']);
    }

    /**
     * @param  string  $oneTimePassword
     * @return bool
     */
    public function checkPassword($oneTimePassword)
    {
        if ($this->isExpired()) {
            return false;
        }

        $log = $this->oneTimePasswordLogs()->where('status', 'waiting')->first();
        if (! $log) {
            return false;
        }

        if (config('otp.encode_password', false)) {
            return Hash::check($oneTimePassword, $log->otp_code);
        }

        return hash_equals((string) $log->otp_code, (string) $oneTimePassword);
    }

    /**
     * @param  Authenticatable|null  $user
     * @return int
     */
    public function acceptEntrance($user = null)
    {
        if ($user) {
            $this->otpUser = $user;
        }

        $userId = $this->otpUser
            ? $this->otpUser->{config('otp.user_id_field', 'id')}
            : $this->user_id;

        $this->update(['status' => 'verified']);
        $this->oneTimePasswordLogs()->where('status', 'discarded')->delete();
        static::where(['status' => 'discarded', 'user_id' => $userId])->delete();

        return $this->oneTimePasswordLogs()
            ->where('user_id', $userId)
            ->where('status', 'waiting')
            ->update(['status' => 'verified']);
    }

    /**
     * Record a wrong-code attempt against the current waiting log.
     * Returns true when the attempt limit is exhausted — the caller must
     * treat the OTP as invalidated and end the pending login.
     */
    public function recordFailedAttempt(): bool
    {
        $log = $this->oneTimePasswordLogs()
            ->where('status', 'waiting')
            ->orderByDesc('id')
            ->first();

        if (! $log) {
            return true;
        }

        $attempts = (int) $log->attempts + 1;
        $max = max(1, (int) config('otp.max_attempts', 5));

        if ($attempts >= $max) {
            $log->update(['attempts' => $attempts, 'status' => 'discarded']);
            $this->update(['status' => 'discarded']);

            return true;
        }

        $log->update(['attempts' => $attempts]);

        return false;
    }

    /**
     * Expiry is anchored to the newest OTP log entry — i.e. when the current
     * code was issued — not to the parent row, which is created once per user
     * and would make every re-issued OTP instantly stale.
     */
    public function isExpired(): bool
    {
        $timeout = (int) config('otp.otp_timeout', 600);

        $latest = $this->oneTimePasswordLogs()->orderByDesc('id')->first();
        $anchor = $latest !== null && $latest->created_at !== null
            ? $latest->created_at
            : $this->created_at;

        if ($anchor === null) {
            return true;
        }

        return $anchor instanceof Carbon
            ? $anchor->lt(Carbon::now()->subSeconds($timeout))
            : Carbon::parse($anchor)->lt(Carbon::now()->subSeconds($timeout));
    }

    /**
     * @param  Authenticatable  $user
     * @return int
     */
    public function discardOldOtpPasswords($user)
    {
        $this->otpUser = $user;
        $userId = $user->{config('otp.user_id_field', 'id')};

        static::where(['status' => 'waiting', 'user_id' => $userId])->update(['status' => 'discarded']);

        return $this->oneTimePasswordLogs()
            ->where('user_id', $userId)
            ->where('status', 'waiting')
            ->update(['status' => 'discarded']);
    }

    /**
     * @return class-string<Model>
     */
    private function guessUserModel()
    {
        foreach (['App\\Models\\User', 'App\\User'] as $model) {
            if (class_exists($model) && is_subclass_of($model, Model::class)) {
                return $model;
            }
        }

        return User::class;
    }
}
