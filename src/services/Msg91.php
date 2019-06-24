<?php

namespace adeshsuryan\LaravelOTPLogin\Services;

use App\User;
use adeshsuryan\LaravelOTPLogin\ServiceInterface;

/**
 * Nexmo SMS service handler
 *
 * @namespace adeshsuryan\LaravelOTPLogin\Services
 */
class Msg91 implements ServiceInterface
{
    /**
     * API key given by nexmo
     *
     * @var string
     */
    private $api_key;

    /**
     * API Secret given by nexmo
     *
     * @var string
     */
    private $api_secret;

    /**
     * The message to be send to the user
     *
     * @var [type]
     */
    private $message;

    /**
     * The User model's phone field name to be used for sending the SMS
     *
     * @var string
     */
    private $phone_column;

    /**
     * FROM number given by nexmo
     *
     * @var string
     */
    private $from;

    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    private $route;

    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    private $country;

    /**
     *
     */
    const RESPONSE_TYPE = 'json';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->from = config('otp.services.msg91.sender', "");
        $this->api_key = config('otp.services.msg91.api_key', "");
        $this->route = config('otp.services.msg91.route', "");
        $this->country = config('otp.services.msg91.country', "");
        $this->message = trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.user_phone_field');
    }

    /**
     * Sends the generated password to the user and returns if it's successful
     *
     * @param App\User $user
     * @param string $otp
     * @param string $ref
     * @return boolean
     */
    public function sendOneTimePassword($user, $otp, $ref)
    {
        // extract the phone from the user
        $user_phone = data_get($user, $this->phone_column, false);

        // if the phone isn't set, return false
        if (!$user_phone) return false;

        try {
            // prepare the request url
            $url = 'https://control.msg91.com/api/sendhttp.php?' . http_build_query([
                    'authkey' => $this->api_key,
                    'route' => $this->route,
                    'country' => $this->country,
                    'mobiles' => $ref,
                    'sender' => $this->from,
                    'response' => self::RESPONSE_TYPE,
                    'message' => iconv("UTF-8", "ASCII//TRANSLIT", str_replace(":password", $otp, $this->message))
                ]);

            // prepare the CURL channel
            $ch = curl_init($url);

            //Ignore SSL certificate verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            // should return the transfer
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // execute the request
            $response = curl_exec($ch);

            // check if response contains the succeeded flag
            return strpos($response, "\"code\": \"0\",") !== false;

        } catch (\Exception $e) {

            // return false if any exception occurs
            return false;
        }
    }
}
