<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use adeshsuryan\LaravelOTPLogin\Mail\OneTimePasswordMail;
use adeshsuryan\LaravelOTPLogin\ServiceInterface;
use Illuminate\Support\Facades\Mail;

class MailChannel implements ServiceInterface
{
    public function sendOneTimePassword($user, $otp, $ref)
    {
        $emailField = config('otp.user_email_field', 'email');
        $email = data_get($user, $emailField);

        if (! $email) {
            return false;
        }

        try {
            $mailable = new OneTimePasswordMail($otp, $ref);
            $mailer = config('otp.services.mail.mailer');

            if ($mailer) {
                Mail::mailer($mailer)->to($email)->send($mailable);
            } else {
                Mail::to($email)->send($mailable);
            }

            (new ArrayChannel)->sendOneTimePassword($user, $otp, $ref);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
