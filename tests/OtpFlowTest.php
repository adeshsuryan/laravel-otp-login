<?php

namespace adeshsuryan\LaravelOTPLogin\Tests;

use adeshsuryan\LaravelOTPLogin\OneTimePassword;
use adeshsuryan\LaravelOTPLogin\Services\ArrayChannel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class OtpFlowTest extends TestCase
{
    public function test_array_channel_issues_and_verifies_otp()
    {
        $user = $this->makeUser();

        $record = OneTimePassword::create([
            'user_id' => $user->id,
            'status' => 'waiting',
        ]);

        $result = $record->send($user);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['otp']);
        $this->assertSame($result['otp'], ArrayChannel::last($user->id)['otp']);
        $this->assertTrue($record->fresh()->checkPassword($result['otp']));
        $this->assertFalse($record->fresh()->checkPassword('000000'));

        $record->acceptEntrance($user);
        $this->assertSame('verified', $record->fresh()->status);
    }

    public function test_expired_otp_is_rejected()
    {
        $user = $this->makeUser();
        config()->set('otp.otp_timeout', 1);
        config()->set('otp.encode_password', false);

        $record = OneTimePassword::create([
            'user_id' => $user->id,
            'status' => 'waiting',
        ]);

        $otp = '123456';
        $log = $record->oneTimePasswordLogs()->create([
            'user_id' => $user->id,
            'otp_code' => $otp,
            'refer_number' => '15551234567',
            'status' => 'waiting',
        ]);

        // Age the log row — expiry is anchored to the latest log entry.
        \adeshsuryan\LaravelOTPLogin\OneTimePasswordLog::whereKey($log->id)->update([
            'created_at' => now()->subMinutes(5),
        ]);

        OneTimePassword::whereKey($record->id)->update([
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $record = $record->fresh();
        $this->assertTrue($record->isExpired());
        $this->assertFalse($record->checkPassword($otp));
    }

    private function makeUser()
    {
        $user = new class extends Authenticatable {
            protected $table = 'users';
            protected $guarded = [];
        };

        $user->forceFill([
            'name' => 'Test User',
            'email' => 'otp@example.com',
            'password' => Hash::make('password'),
            'phone' => '5551234567',
            'dialingcode' => '+1',
        ])->save();

        return $user->fresh();
    }
}
