<?php

namespace adeshsuryan\LaravelOTPLogin\Console;

use adeshsuryan\LaravelOTPLogin\TrustedDevice;
use Illuminate\Console\Command;

class PruneCommand extends Command
{
    protected $signature = 'otp:prune';

    protected $description = 'Delete expired OTP trusted-device tokens and discarded OTP log rows.';

    public function handle()
    {
        $devices = TrustedDevice::pruneExpired();
        $this->info("Pruned {$devices} expired trusted-device token(s).");

        return self::SUCCESS;
    }
}
