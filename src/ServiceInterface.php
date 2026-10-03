<?php

namespace adeshsuryan\LaravelOTPLogin;

use Illuminate\Contracts\Auth\Authenticatable;

interface ServiceInterface
{
    /**
     * @param  Authenticatable  $user
     * @param  string  $otp
     * @param  string  $ref
     * @return bool
     */
    public function sendOneTimePassword($user, $otp, $ref);
}
