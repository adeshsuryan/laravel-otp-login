<?php

namespace adeshsuryan\LaravelOTPLogin\Tests;

use Illuminate\Support\Facades\Schema;

class SecurityMigrationTest extends TestCase
{
    public function test_security_upgrade_schema_is_applied()
    {
        $this->assertTrue(Schema::hasTable('otp_trusted_devices'));
        $this->assertTrue(Schema::hasColumns('otp_trusted_devices', [
            'user_id', 'token_hash', 'agent_hash', 'expires_at',
        ]));
        $this->assertTrue(Schema::hasColumn('one_time_password_logs', 'attempts'));
    }

    public function test_security_upgrade_down_rolls_back()
    {
        $migration = new \OtpSecurityUpgrade;
        $migration->down();

        $this->assertFalse(Schema::hasTable('otp_trusted_devices'));
        $this->assertFalse(Schema::hasColumn('one_time_password_logs', 'attempts'));

        // Re-apply so the rest of the suite sees the migrated schema.
        $migration->up();
        $this->assertTrue(Schema::hasTable('otp_trusted_devices'));
    }
}
