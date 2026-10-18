<?php

namespace adeshsuryan\LaravelOTPLogin\Tests;

use adeshsuryan\LaravelOTPLogin\Mail\OneTimePasswordMail;
use adeshsuryan\LaravelOTPLogin\OneTimePassword;
use adeshsuryan\LaravelOTPLogin\Services\ArrayChannel;
use adeshsuryan\LaravelOTPLogin\Services\LogChannel;
use adeshsuryan\LaravelOTPLogin\Services\MailChannel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChannelContractTest extends TestCase
{
    public function test_mail_channel_sends_mailable_to_user_email()
    {
        Mail::fake();
        $user = $this->makeUser();

        $result = (new MailChannel)->sendOneTimePassword($user, '424242', '15551234567');

        $this->assertTrue($result);
        Mail::assertSent(OneTimePasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
        // Mail channel also stashes the payload for the array-channel API.
        $this->assertSame('424242', ArrayChannel::last($user->id)['otp']);
    }

    public function test_mail_channel_returns_false_without_email()
    {
        Mail::fake();
        $user = new Authenticatable;
        $user->id = 7;

        $result = (new MailChannel)->sendOneTimePassword($user, '424242', '15551234567');

        $this->assertFalse($result);
        Mail::assertNothingSent();
    }

    public function test_log_channel_logs_and_stashes_payload()
    {
        Log::spy();
        $user = $this->makeUser();

        $result = (new LogChannel)->sendOneTimePassword($user, '515151', '15551234567');

        $this->assertTrue($result);
        Log::shouldHaveReceived('info')->once()->with('laravel-otp-login OTP issued', \Mockery::on(function ($context) use ($user) {
            return $context['user_id'] === $user->id
                && $context['otp'] === '515151'
                && $context['ref'] === '15551234567';
        }));
        $this->assertSame('515151', ArrayChannel::last($user->id)['otp']);
    }

    public function test_user_model_override_is_honoured()
    {
        config()->set('otp.user_model', User::class);
        $user = $this->makeUser();

        $otp = OneTimePassword::create([
            'user_id' => $user->id,
            'status' => 'waiting',
        ]);

        $this->assertInstanceOf(User::class, $otp->user);
    }

    protected function makeUser()
    {
        $user = new Authenticatable;
        $user->name = 'Channel Test';
        $user->email = 'channel-'.uniqid().'@example.com';
        $user->password = bcrypt('secret');
        $user->phone = '5551234567';
        $user->dialingcode = '+1';
        $user->save();

        return $user;
    }
}
