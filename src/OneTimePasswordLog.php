<?php

namespace adeshsuryan\LaravelOTPLogin;

use Illuminate\Database\Eloquent\Model;

class OneTimePasswordLog extends Model
{
    protected $table = 'one_time_password_logs';

    protected $fillable = ['user_id', 'otp_code', 'status', 'refer_number', 'attempts'];
}
