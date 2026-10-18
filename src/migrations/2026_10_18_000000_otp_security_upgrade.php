<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security upgrade for the *.1.0 line:
 *  - attempts counter on one_time_password_logs for brute-force lockout
 *  - drop the plaintext otp_code index (OTP is never looked up by value)
 *  - otp_trusted_devices table: random server-side tokens backing the
 *    "verified" cookie, replacing the predictable user_id_{id} value
 */
class OtpSecurityUpgrade extends Migration
{
    public function up(): void
    {
        Schema::table('one_time_password_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('attempts')->default(0)->after('status');
        });

        try {
            Schema::table('one_time_password_logs', function (Blueprint $table) {
                $table->dropIndex(['otp_code']);
            });
        } catch (Throwable $e) {
            // Index may not exist on installations that already removed it.
        }

        $userTable = config('otp.user_table', 'users');
        $trustedTable = config('otp.trusted_devices_table', 'otp_trusted_devices');

        Schema::create($trustedTable, function (Blueprint $table) use ($userTable) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token_hash', 64)->index();
            $table->string('agent_hash', 64)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            if (config('database.default') !== 'sqlite' && config('database.default') !== 'testing') {
                $table->foreign('user_id')
                    ->references('id')
                    ->on($userTable)
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('otp.trusted_devices_table', 'otp_trusted_devices'));

        Schema::table('one_time_password_logs', function (Blueprint $table) {
            $table->dropColumn('attempts');
            $table->index('otp_code');
        });
    }
}
