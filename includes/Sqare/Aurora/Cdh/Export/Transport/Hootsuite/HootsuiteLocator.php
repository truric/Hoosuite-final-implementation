<?php

namespace Sqare\Aurora\Cdh\Export\Transport\Hootsuite;

use Exception;

class HootsuiteLocator
{
    const SERVICEURL = "http://www.geoplugin.net/php.gp";

    /**
     * @param string|null $ip
     * @return array|null
     */
    public static function getLocation(string $ip = null): ?array
    {
        // https://www.php.net/manual/en/reserved.variables.server.php
        if (!$ip)
        {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '179.250.125.000';
        }

        $result = file_get_contents(sprintf("%s?ip=%s", self::SERVICEURL, $ip));

        $data = unserialize($result);

        if($data['geoplugin_latitude'] && $data['geoplugin_longitude'])
        {
            return [$data['geoplugin_latitude'],$data['geoplugin_longitude']];
        }else{
//            throw new Exception('Service content has been changed or no longer working.');
            return null;
        }
    }
}
