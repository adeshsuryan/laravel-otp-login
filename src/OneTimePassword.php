<?php

namespace adeshsuryan\LaravelOTPLogin;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Class OneTimePassword
 * @package adeshsuryan\LaravelOTPLogin
 */
class OneTimePassword extends Model
{
    /**
     * @var array
     */
    protected $fillable = ["user_id", "status"];

    /**
     * @var
     */
    protected $user;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function oneTimePasswordLogs()
    {
        return $this->hasMany(OneTimePasswordLog::class, "user_id", "user_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, "id", "user_id");
    }

    /**
     * @param $user
     * @return bool|null
     */
    public function send($user)
    {
        $this->user = $user;
        $ref = $this->user->dialingcode.$this->user->phone;
        $otp = $this->createOTP($ref);
        $sentOtp['otp'] = $otp;
        if (!empty($otp)) {
            if (config("otp.otp_service_enabled", false)) {
                $sentOtp['status'] = $this->sendOTPWithService($this->user, $otp, trim($ref,'+'));
                return $sentOtp;
            }
            return true;
        }
        return null;
    }

    /**
     * @param $user
     * @param $otp
     * @param $ref
     * @return bool
     */
    private function sendOTPWithService($user, $otp, $ref)
    {
        $OTPFactory = new ServiceFactory();
        $service = $OTPFactory->getService(config("otp.otp_default_service", null));
        if ($service) {
            return $service->sendOneTimePassword($user, $otp, $ref);
        }
        return false;
    }

    /**
     * @param $ref
     * @return bool|string
     */
    public function createOTP($ref)
    {
        $this->discardOldPasswords();
        $otp = $this->OTPGenerator();

        $otp_code = $otp;

        if (config("otp.encode_password", false)) {
            $otp_code = Hash::make($otp);
        }

        $this->update(["status" => "waiting"]);

        $this->oneTimePasswordLogs()->create([
            'user_id' => $this->user->id,
            'otp_code' => $otp_code,
            'refer_number' => $ref,
            'status' => 'waiting',
        ]);

        return $otp;
    }

    /**
     * @return bool|string
     */
    private function ReferenceNumber()
    {
        $number = strval(rand(100000000, 999999999));
        return substr($number, 0, config("otp.otp_reference_number_length", 4));
    }

    /**
     * @return bool|string
     */
    private function OTPGenerator()
    {
        $number = strval(rand(100000000, 999999999));
        return substr($number, 0, config("otp.otp_digit_length", 4));
    }

    /**
     * @return int
     */
    public function discardOldPasswords()
    {
        $this->update(["status" => "discarded"]);
        return $this->oneTimePasswordLogs()->whereIn("status", ["waiting", "verified"])->update(["status" => "discarded"]);

    }

    /**
     * @param $oneTimePassword
     * @return bool
     */
    public function checkPassword($oneTimePassword)
    {
        $oneTimePasswordLog = $this->oneTimePasswordLogs()
            ->where("status", "waiting")->first();

        if (!empty($oneTimePasswordLog)) {

            if (config("otp.encode_password", false)) {
                return Hash::check($oneTimePassword, $oneTimePasswordLog->otp_code);
            } else {
                return $oneTimePasswordLog->otp_code == $oneTimePassword;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function acceptEntrance()
    {
        $this->update(["status" => "verified"]);
        $this->oneTimePasswordLogs()->where("status", "discarded")->delete();
        OneTimePassword::where(["status" => "discarded", "user_id" => $this->user->id])->delete();
        return $this->oneTimePasswordLogs()->where("user_id", $this->user->id)->where("status", "waiting")->update(["status" => "verified"]);
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->created_at < Carbon::now()->subSeconds(config("otp.otp_timeout"));
    }

    /**
     * @param $user
     * @return int
     */
    public function discardOldOtpPasswords($user)
    {
        $this->user = $user;
        OneTimePassword::where(["status" => "waiting", "user_id" => $this->user->id])->update(["status" => "discarded"]);
        return $this->oneTimePasswordLogs()->where("user_id", $this->user->id)->where("status", "waiting")->update(["status" => "discarded"]);
    }
}
