<?php

namespace App\Service\Novel;

use App\Service\Logger;
use App\Service\Util;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

abstract class NovelBaseService
{
    // 网址
    public $baseUri = '';

    // 搜索
    abstract public function search(string $keyword): ?array;

    // 目录
    abstract public function novelDir(string $uri): ?array;

    // 章节正文
    abstract public function novelChapter(string $uri): ?string;

    // 热门榜单
    abstract public function hotList(): ?array;

    /**
     * 抓取网页html
     *
     * @param string $uri
     * @param array $params
     * @return string|null
     */
    protected function spiderHtml(string $uri, array $params = []): ?string
    {
        try {
            $url = $this->baseUri . ltrim($uri, '/');
            Logger::info(sprintf('request url: %s, params: %s', $url, json_encode($params)));
            $resp = Util::getHttpClient()->request(
                'GET',
                $url,
                [
                    RequestOptions::QUERY => $params
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error('request fail! code: ' . $resp->getStatusCode());
                return null;
            }

            $content = $resp->getBody()->getContents();
            Logger::info('request success! response: ' . substr($content, 0, 100));
            return $content;
        } catch (GuzzleException | Exception $e) {
            Logger::error(
                sprintf('spiderHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            return null;
        }
    }
}
