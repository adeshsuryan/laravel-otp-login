<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpLogsTable extends Migration
{
    public function up(): void
    {
        $userTable = config('otp.user_table', 'users');

        Schema::create('one_time_password_logs', function (Blueprint $table) use ($userTable) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('otp_code')->index();
            $table->string('refer_number')->index();
            $table->string('status')->index();
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
        Schema::dropIfExists('one_time_password_logs');
    }
}
