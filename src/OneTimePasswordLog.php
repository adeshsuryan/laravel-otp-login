<?php

namespace adeshsuryan\LaravelOTPLogin;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|string $user_id
 * @property string $otp_code
 * @property string $refer_number
 * @property string $status
 * @property int $attempts
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OneTimePasswordLog extends Model
{
    protected $table = 'one_time_password_logs';

    protected $fillable = ['user_id', 'otp_code', 'status', 'refer_number', 'attempts'];
}
