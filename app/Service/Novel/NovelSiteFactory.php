<?php

namespace App\Service\Novel;

use App\Service\Logger;

class NovelSiteFactory
{
    public static $novelSites = [
        'xbqg' => [
            'name' => '新笔趣阁',
            'host' => 'https://www.xsbiquge.com/',
            'search' => true,
            'hotlist' => true,
        ],
        'biquku' => [
            'name' => '笔趣阁',
            'host' => 'http://www.biquku.la/',
            'search' => true,
            'hotlist' => true,
        ],
        'bxwx' => [
            'name' => '笔下文学',
            'host' => 'http://www.bxwx666.org/',
            'search' => true,
            'hotlist' => true,
        ]
    ];

    /**
     * 获取不同网站实例
     *
     * @param string $site
     * @return NovelBaseService|null
     */
    public static function getService(string $site): ?NovelBaseService
    {
        if (!in_array($site, ['xbqg', 'biquku', 'bxwx'])) {
            Logger::error('error site: ' . $site);
            return null;
        }
        return app($site);
    }
}
