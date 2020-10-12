<?php

namespace App\Service\Novel;

use App\Service\Logger;

class NovelSiteFactory
{
    private static $containers = [];

    /**
     * 获取不同网站实例
     *
     * @param string $site
     * @return NovelBaseService|null
     */
    public static function getService(string $site): ?NovelBaseService
    {
        switch ($site) {
            case 'xbqg':
                if (!isset(self::$containers['xbqg'])) {
                    self::$containers['xbqg'] = new XbqgService();
                }
                return self::$containers['xbqg'];
            case 'biquku':
                if (!isset(self::$containers['biquku'])) {
                    self::$containers['biquku'] = new BiqukuService();
                }
                return self::$containers['biquku'];
            case 'bxwx':
                if (!isset(self::$containers['bxwx'])) {
                    self::$containers['bxwx'] = new BxwxService();
                }
                return self::$containers['bxwx'];
            default:
                Logger::error('error site: ' . $site);
                return null;
        }
    }
}
