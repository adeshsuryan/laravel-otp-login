<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpTable extends Migration
{
    public function up()
    {
        $userTable = config('otp.user_table', 'users');

        Schema::create('one_time_passwords', function (Blueprint $table) use ($userTable) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
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

    public function down()
    {
        Schema::dropIfExists('one_time_passwords');
    }
}
