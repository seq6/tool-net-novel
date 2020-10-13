<?php

namespace App\Service\Novel;

use App\Service\DOMHelp;
use App\Service\Logger;
use App\Service\Util;
use DateTime;
use DOMDocument;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;

/**
 * 笔下文学
 *
 * Class BxwxService
 * @package App\Service\Novel
 */
class BxwxService extends NovelBaseService
{
    const BXWX_COOKIE_KEY = 'bxwx_cookie';

    public $baseUri = 'http://www.bxwx666.org/';

    private $hotListUri = 'http://www.bxwx666.org/ph/1.htm';
    private $searchUri = 'http://www.bxwx666.org/search.aspx';

    public function search(string $keyword): ?array
    {
        try {
            $keyword = urlencode(mb_convert_encoding($keyword, 'GB2312'));
            $resp = Util::getHttpClient()->request(
                'GET',
                $this->searchUri,
                [
                    RequestOptions::QUERY => ['bookname' => $keyword],
                    RequestOptions::HEADERS => ['Cookie' => $this->getCookie(), 'Referer' => $this->hotListUri]
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $this->searchUri, $resp->getStatusCode()));
                return null;
            }

            // 解析html
            $content = $resp->getBody()->getContents();
            if (empty($content)) {
                Logger::error('spider html is empty.');
                return null;
            }
            $content = mb_convert_encoding($content, 'UTF-8', 'GB2312');
            return $this->parseSearchHtml($content);
        } catch (GuzzleException | Exception $e) {
            Logger::error(
                sprintf('spider bxwx search html fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            return null;
        }
    }

    public function novelDir(string $uri): ?array
    {
        try {
            // 基本信息
            $url = $this->baseUri . $uri;
            $cookie = $this->getCookie();
            $resp = Util::getHttpClient()->request('GET', $url, [RequestOptions::HEADERS => ['Cookie' => $cookie]]);
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                return null;
            }
            $content = $resp->getBody()->getContents();
            if (empty($content)) {
                Logger::error('spider html is empty.');
                return null;
            }
            $content = mb_convert_encoding($content, 'UTF-8', 'GB2312');
            $info = $this->parseNovelBaseHtml($content);
            if (empty($info)) {
                return null;
            }

            // 章节列表
            $url = $this->baseUri . 'ashx/zj.ashx';
            $xsid = intval(str_replace('txt/', '', trim($uri, '/')));
            $resp = Util::getHttpClient()->request(
                'POST',
                $url,
                [
                    RequestOptions::HEADERS => ['Cookie' => $cookie],
                    RequestOptions::FORM_PARAMS => ['action' => 'GetZj', 'xsid' => $xsid]
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                return null;
            }
            $content = $resp->getBody()->getContents();
            if (empty($content)) {
                Logger::error('spider html is empty.');
                return null;
            }
            $content = mb_convert_encoding($content, 'UTF-8', 'GB2312');
            $info['chapters'] = $this->parseNovelChaptersHtml($content);
            return $info;
        } catch (GuzzleException | Exception $e) {
            Logger::error(
                sprintf('spider bxwx html fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            return null;
        }
    }

    public function novelChapter(string $uri): ?string
    {
        $html = $this->spiderHtml($uri);
        if (empty($html)) {
            Logger::error('spider html is empty.');
            return null;
        }
        $html = mb_convert_encoding($html, 'UTF-8', 'GB2312');
        return $this->parseChapterHtml($html);
    }

    public function hotList(): ?array
    {
        try {
            Logger::info(sprintf('request url: %s', $this->hotListUri));
            $resp = Util::getHttpClient()->request('GET', $this->hotListUri);
            if ($resp->getStatusCode() != 200) {
                Logger::error('request fail! code: ' . $resp->getStatusCode());
                return null;
            }

            // 缓存cookie
            $setCookie = $resp->getHeader('Set-Cookie');
            $this->cacheCookie($setCookie[0]);

            // 解析html
            $content = $resp->getBody()->getContents();
            if (empty($content)) {
                Logger::error('spider html is empty.');
                return null;
            }
            $content = mb_convert_encoding($content, 'UTF-8', 'GB2312');
            return $this->parseHotListHtml($content);
        } catch (GuzzleException | Exception $e) {
            Logger::error(
                sprintf('spider bxwx rank html fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            return null;
        }
    }

    private function cacheCookie(string $setCookie): string
    {
        $t = (new DateTime())->format('Y-m-d_H:i:s:u');
        $r = rand(1, 1000);
        $cuid = 'www.bxwx666.org_' . $t . '_' . $r;
        $cookie = 'LookNum=1; cuid=' . $cuid . '; ' . $setCookie;
        // cookie缓存60分钟
        Cache::add(self::BXWX_COOKIE_KEY, $cookie, 3600);
        return $cookie;
    }

    private function getCookie(): ?string
    {
        $cookie = Cache::get(self::BXWX_COOKIE_KEY, '');
        Logger::info('bxwx cache: cookie=' . $cookie);
        if (empty($cookie)) {
            // 从首页获取cookie信息
            $url = $this->baseUri . 'ph/1.htm';
            $resp = Util::getHttpClient()->request('GET', $url);
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                return null;
            }
            $setCookie = $resp->getHeader('Set-Cookie');
            $cookie = $this->cacheCookie($setCookie[0]);
        }
        return $cookie;
    }

    /**
     * 解析搜索页面html
     *
     * @param string $html
     * @return array|null
     */
    private function parseSearchHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $content = $dom->getElementById('newscontent');
        if (empty($content)) {
            return null;
        }
        $div = DOMHelp::getFirstNodeByClass($content->childNodes, 'l');
        if (empty($div)) {
            return null;
        }
        $ul = DOMHelp::getFirstNodeByTag($div->childNodes, 'ul');
        if (empty($ul)) {
            return null;
        }

        $list = [];
        foreach (DOMHelp::getNodesByTag($ul->childNodes, 'li') as $li) {
            $item = [];
            foreach (DOMHelp::getNodesByTag($li->childNodes, 'span') as $span) {
                $class = $span->attributes->getNamedItem('class');
                if (empty($class)) {
                    continue;
                }

                switch (trim($class->textContent)) {
                    case 's1':
                        $item['category'] = trim($span->textContent, '[]');
                        break;
                    case 's2':
                        $item['title'] = trim($span->textContent);
                        $a = DOMHelp::getFirstNodeByTag($span->childNodes, 'a');
                        if (!empty($a)) {
                            $uri = $a->attributes->getNamedItem('href')->textContent;
                            $item['href'] = str_replace($this->baseUri, '', $uri);
                        }
                        break;
                    case 's3':
                        $item['latest_chapter_name'] = trim($span->textContent);
                        $a = DOMHelp::getFirstNodeByTag($span->childNodes, 'a');
                        if (!empty($a)) {
                            $uri = $a->attributes->getNamedItem('href')->textContent;
                            $item['latest_chapter_url'] = str_replace($this->baseUri, '', $uri);
                        }
                        break;
                    case 's4':
                        $item['author'] = trim($span->textContent);
                        break;
                    case 's5':
                        $item['updated_at'] = trim($span->textContent);
                        break;
                }
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 解析热门榜单html
     *
     * @param string $html
     * @return array|null
     */
    private function parseHotListHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $content = $dom->getElementById('newscontent');
        if (empty($content)) {
            return null;
        }
        $uls = $content->getElementsByTagName('ul');
        if ($uls->count() == 0) {
            return null;
        }

        $list = [];
        foreach (DOMHelp::getNodesByTag($uls->item(0)->childNodes, 'li') as $li) {
            $item = [];
            foreach (DOMHelp::getNodesByTag($li->childNodes, 'span') as $span) {
                $class = $span->attributes->getNamedItem('class');
                if (empty($class)) {
                    continue;
                }

                switch (trim($class->textContent)) {
                    case 's1':
                        $item['category'] = trim($span->textContent, '[]');
                        break;
                    case 's2':
                        $item['title'] = trim($span->textContent);
                        $a = DOMHelp::getFirstNodeByTag($span->childNodes, 'a');
                        if (!empty($a)) {
                            $uri = $a->attributes->getNamedItem('href')->textContent;
                            $item['uri'] = str_replace($this->baseUri, '', $uri);
                        }
                        break;
                    case 's3':
                        $item['latest_chapter_name'] = trim($span->textContent);
                        $a = DOMHelp::getFirstNodeByTag($span->childNodes, 'a');
                        if (!empty($a)) {
                            $uri = $a->attributes->getNamedItem('href')->textContent;
                            $item['latest_chapter_url'] = trim($uri, '/');
                        }
                        break;
                    case 's4':
                        $item['author'] = trim($span->textContent);
                        break;
                    case 's5':
                        $item['updated_at'] = trim($span->textContent);
                        break;
                }
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 解析小说基本信息html
     *
     * @param string $html
     * @return array|null
     */
    private function parseNovelBaseHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $info = [];
        $metas = $dom->getElementsByTagName('meta');
        for ($i = 0, $n = $metas->count(); $i < $n; $i++) {
            $meta = $metas->item($i);
            if (empty($meta) || empty($meta->attributes)) {
                continue;
            }
            $property = $meta->attributes->getNamedItem('property');
            $content = $meta->attributes->getNamedItem('content');
            if (empty($property) || empty($content)) {
                continue;
            }

            switch (trim($property->textContent)) {
                // 书名
                case 'og:title'  :
                    $info['title'] = trim($content->textContent);
                    break;
                // 简介
                case 'og:description':
                    $info['intro'] = str_replace([' ', '	', '　', "\n"], '', trim($content->textContent));
                    break;
                // 封面
                case 'og:image':
                    $info['cover'] = str_replace($this->baseUri, '', trim($content->textContent));
                    break;
                // 类型
                case 'og:novel:category':
                    $info['category'] = trim($content->textContent);
                    break;
                // 作者
                case 'og:novel:author':
                    $info['author'] = trim($content->textContent);
                    break;
                // 最后更新时间
                case 'og:novel:update_time':
                    $info['updated_at'] = trim($content->textContent);
                    break;
                // 最新章节名称
                case 'og:novel:latest_chapter_name':
                    $info['latest_chapter_name'] = trim($content->textContent);
                    break;
                // 最新章节链接
                case 'og:novel:latest_chapter_url':
                    $info['latest_chapter_url'] = str_replace($this->baseUri, '', trim($content->textContent));
                    break;
            }
        }
        return $info;
    }

    /**
     * 解析章节目录xml
     *
     * @param string $html
     * @return array|null
     */
    private function parseNovelChaptersHtml(string $html): ?array
    {
        $chapters = [];
        $html = str_replace(['<dd>', '</dd>', '<a href="http://www.bxwx666.org/'], '', $html);
        $arr = explode('</a>', $html);
        foreach ($arr as $idx => $line) {
            $exp = explode('">', $line);
            if (count($exp) != 2) {
                continue;
            }
            $seq = $idx + 1;
            $chapters[$seq] = [
                'seq' => $seq,
                'title' => trim($exp[1]),
                'uri' => trim($exp[0])
            ];
        }
        return $chapters;
    }

    /**
     * 解析章节正文html
     *
     * @param string $html
     * @return string|null
     */
    private function parseChapterHtml(string $html): ?string
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $contents = $dom->getElementById('zjneirong');
        if (empty($contents)) {
            return null;
        }

        $lines = [];
        foreach (DOMHelp::getNodesByTag($contents->childNodes, 'p') as $p) {
            $line = trim($p->textContent);
            if (empty($line)) {
                continue;
            }
            $lines[] = str_replace([" ", "﻿"], '', $line);
        }
        return implode("\n", $lines);
    }
}
