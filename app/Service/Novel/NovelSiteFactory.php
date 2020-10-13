<?php

namespace App\Service\Novel;

use App\Service\Logger;

class NovelSiteFactory
{
    public static $novelSites = [
        'xbqg' => [
            'name' => '新笔趣阁',
            'search' => true,
            'hotlist' => true,
        ],
        'biquku' => [
            'name' => '笔趣阁',
            'search' => true,
            'hotlist' => true,
        ],
        'bxwx' => [
            'name' => '笔下文学',
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
