<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Util
{
    private static $httpClient;

    /**
     * 获取HTTP连接
     *
     * @param int $timeout
     * @return Client
     */
    public static function getHttpClient(int $timeout = 20)
    {
        if (empty(self::$httpClient)) {
            self::$httpClient = new Client(
                [
                    RequestOptions::TIMEOUT => $timeout
                ]
            );
        }
        return self::$httpClient;
    }
}
