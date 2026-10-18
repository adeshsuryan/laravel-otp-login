<?php

namespace adeshsuryan\LaravelOTPLogin\Tests;

use adeshsuryan\LaravelOTPLogin\LoginMiddleware;
use adeshsuryan\LaravelOTPLogin\OneTimePassword;
use adeshsuryan\LaravelOTPLogin\OneTimePasswordLog;
use adeshsuryan\LaravelOTPLogin\Services\ArrayChannel;
use adeshsuryan\LaravelOTPLogin\TrustedDevice;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class OtpSecurityTest extends TestCase
{
    public function test_codes_stored_hashed_by_default()
    {
        config()->set('otp.encode_password', true);
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $result = $record->send($user);

        $log = OneTimePasswordLog::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotSame($result['otp'], $log->otp_code, 'OTP must not be stored in plaintext');
        $this->assertTrue(strlen($log->otp_code) >= 50, 'expected a hash, got '.strlen($log->otp_code).' chars');
        $this->assertTrue($record->fresh()->checkPassword($result['otp']));
        $this->assertFalse($record->fresh()->checkPassword('000000'));
    }

    public function test_failed_attempts_lock_out_after_limit()
    {
        config()->set('otp.max_attempts', 3);
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $record->send($user);

        $this->be($user);

        for ($i = 0; $i < 2; $i++) {
            $response = $this->post(route('otp.verify'), ['code' => '111111']);
            $response->assertRedirect(route('otp.view'));
        }

        $log = OneTimePasswordLog::where('user_id', $user->id)->latest('id')->first();
        $this->assertSame(2, (int) $log->attempts);

        // Third wrong attempt exhausts the limit: OTP discarded + user logged out.
        $response = $this->post(route('otp.verify'), ['code' => '111111']);
        $response->assertRedirect(route('login'));
        $this->assertSame('discarded', $record->fresh()->status);
        $this->assertGuest();
    }

    public function test_correct_code_after_wrong_attempts_still_verifies()
    {
        config()->set('otp.max_attempts', 5);
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $result = $record->send($user);
        $this->be($user);

        $this->post(route('otp.verify'), ['code' => '000000']);
        $response = $this->post(route('otp.verify'), ['code' => $result['otp']]);

        $this->assertSame('verified', $record->fresh()->status);
        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_cookie_is_random_token_not_predictable_user_id()
    {
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $result = $record->send($user);
        $this->be($user);

        $response = $this->post(route('otp.verify'), ['code' => $result['otp']]);

        $cookieName = config('otp.verified_cookie_name');
        $cookie = collect($response->headers->getCookies())->firstWhere(
            fn ($c) => $c->getName() === $cookieName
        );

        $this->assertNotNull($cookie, 'verified cookie must be set');
        // The web group encrypts the cookie — decrypt to inspect the value.
        $value = \Illuminate\Support\Facades\Crypt::decryptString($cookie->getValue());
        $this->assertStringNotContainsString('user_id_', $value);
        $this->assertStringContainsString('|', $value);
        $this->assertSame(1, TrustedDevice::where('user_id', $user->id)->count());
    }

    public function test_forged_user_id_cookie_does_not_bypass_middleware()
    {
        $user = $this->makeUser();
        OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting'])->send($user);
        $this->be($user);

        $this->registerGuardedRoute();

        $cookieName = config('otp.verified_cookie_name');
        // withCookie encrypts like a real browser — proves the value itself
        // is rejected, not just a decryption failure on a raw cookie.
        $response = $this->withCookie($cookieName, 'user_id_'.$user->id)
            ->get('/guarded');

        $response->assertRedirect(route('otp.view'));
    }

    public function test_legitimate_trusted_device_cookie_passes_middleware()
    {
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $record->send($user);
        $this->be($user);

        $this->registerGuardedRoute();

        $cookieName = config('otp.verified_cookie_name');
        $value = TrustedDevice::issue($user->id, 60);
        $response = $this->withCookie($cookieName, $value)
            ->get('/guarded');

        $response->assertOk();
        $response->assertSee('guarded-ok');
    }

    public function test_middleware_fails_closed_when_no_otp_state()
    {
        config()->set('otp.fail_open', false);
        $user = $this->makeUser();
        $this->be($user);
        $this->registerGuardedRoute();

        // No OTP row at all — the gate must not pass the request through.
        $response = $this->get('/guarded');
        $response->assertRedirect(route('otp.view'));

        // A new OTP was issued as part of failing closed.
        $this->assertSame('waiting', OneTimePassword::where('user_id', $user->id)->first()->status);
        $this->assertNotNull(ArrayChannel::last($user->id));
    }

    public function test_middleware_fail_open_escape_hatch()
    {
        config()->set('otp.fail_open', true);
        $user = $this->makeUser();
        $this->be($user);
        $this->registerGuardedRoute();

        $response = $this->get('/guarded');
        $response->assertOk();
    }

    public function test_expiry_anchors_to_latest_log_not_parent_row()
    {
        $user = $this->makeUser();
        $record = OneTimePassword::create([
            'user_id' => $user->id,
            'status' => 'waiting',
            'created_at' => now()->subDays(3), // parent created long ago
        ]);
        $record->save();
        $record->send($user); // issues a fresh log entry now

        $this->assertFalse($record->fresh()->isExpired(),
            'freshly issued OTP must not be stale because the parent row is old');
    }

    public function test_expired_waiting_otp_blocks_verification()
    {
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $result = $record->send($user);

        $latestLog = OneTimePasswordLog::where('user_id', $user->id)->latest('id')->first();
        OneTimePasswordLog::whereKey($latestLog->id)
            ->update(['created_at' => now()->subHours(2)]);

        $this->be($user);
        $response = $this->post(route('otp.verify'), ['code' => $result['otp']]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_code_validation_rejects_non_digits()
    {
        $user = $this->makeUser();
        $record = OneTimePassword::create(['user_id' => $user->id, 'status' => 'waiting']);
        $record->send($user);
        $this->be($user);

        $response = $this->post(route('otp.verify'), ['code' => 'abcdef']);
        $response->assertSessionHasErrors('code');
    }

    public function test_prune_command_removes_expired_devices()
    {
        $user = $this->makeUser();
        TrustedDevice::issue($user->id, -10); // already expired
        TrustedDevice::issue($user->id, 60);
        $this->assertSame(2, TrustedDevice::count());

        $this->artisan('otp:prune')->assertSuccessful();

        $this->assertSame(1, TrustedDevice::count());
    }

    public function test_trusted_device_expiry_enforced()
    {
        $user = $this->makeUser();
        $value = TrustedDevice::issue($user->id, -10);
        $this->assertFalse(TrustedDevice::isTrusted($value, $user->id));
    }

    private function registerGuardedRoute()
    {
        Route::middleware(['web', 'auth', LoginMiddleware::class])
            ->get('/guarded', fn () => response('guarded-ok'));
    }

    private function makeUser()
    {
        $user = new class extends Authenticatable {
            protected $table = 'users';
            protected $guarded = [];
        };

        $user->forceFill([
            'name' => 'Test User',
            'email' => 'otp'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'phone' => '5551234567',
            'dialingcode' => '+1',
        ])->save();

        return $user->fresh();
    }
}
