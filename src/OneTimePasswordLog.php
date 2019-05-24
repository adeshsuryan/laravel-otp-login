<?php

namespace adeshsuryan\LaravelOTPLogin;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OneTimePasswordLog
 * @package adeshsuryan\LaravelOTPLogin
 */
class OneTimePasswordLog extends Model
{
    /**
     * @var array
     */
    protected $fillable = ["user_id", "otp_code", "status", "refer_number"];
}
