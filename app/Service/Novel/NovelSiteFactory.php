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
            default:
                Logger::error('error site: ' . $site);
                return null;
        }
    }
}
