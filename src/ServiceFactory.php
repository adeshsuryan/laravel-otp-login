<?php

namespace adeshsuryan\LaravelOTPLogin;

class ServiceFactory
{
    /**
     * @param  string|null  $serviceName
     * @return ServiceInterface|null
     */
    public function getService($serviceName)
    {
        $services = config('otp.services', []);

        if (
            isset($services[$serviceName]['class'])
            && is_string($services[$serviceName]['class'])
            && class_exists($services[$serviceName]['class'])
        ) {
            $service = new $services[$serviceName]['class'];

            return $service instanceof ServiceInterface ? $service : null;
        }

        return null;
    }
}
