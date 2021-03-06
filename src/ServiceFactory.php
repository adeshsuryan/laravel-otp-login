<?php
namespace adeshsuryan\LaravelOTPLogin;

use adeshsuryan\LaravelOTPLogin\Services;

/**
 * Class ServiceFactory
 * @package adeshsuryan\LaravelOTPLogin
 */
class ServiceFactory
{
    /**
     * @param $serviceName
     * @return null
     */
    public function getService($serviceName)
    {
        $services = config("otp.services", []);
        if (isset($services[$serviceName]) && isset($services[$serviceName]["class"]) && class_exists($services[$serviceName]["class"])) {
            return new $services[$serviceName]["class"]();
        } else {
            return null;
        }
    }
}
