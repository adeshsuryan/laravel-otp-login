<?php

namespace adeshsuryan\LaravelOTPLogin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OneTimePasswordMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var string */
    public $otp;

    /** @var string */
    public $ref;

    public function __construct($otp, $ref)
    {
        $this->otp = (string) $otp;
        $this->ref = (string) $ref;
    }

    public function build()
    {
        $message = __('laravel-otp-login::messages.otp_message', ['password' => $this->otp]);

        return $this->subject(__('laravel-otp-login::messages.verify_phone_title'))
            ->text('laravel-otp-login::mail.otp')
            ->with([
                'otpMessage' => $message,
                'otp' => $this->otp,
                'ref' => $this->ref,
            ]);
    }
}
