<?php

namespace App\Service\Novel;

use App\Service\DOMHelp;
use App\Service\Logger;
use App\Service\Util;
use DOMDocument;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * 笔趣阁
 *
 * Class BiqukuService
 * @package App\Service\Novel
 */
class BiqukuService extends NovelBaseService
{
    public $baseUri = 'http://www.biquku.la/';

    private $searchUri = 'modules/article/search.php';
    private $hotListUri = 'paihangbang/';

    public function search(string $keyword): ?array
    {
        try {
            $url = $this->baseUri . $this->searchUri;
            Logger::info(sprintf('request url: %s, params: keyword=%s', $url, $keyword));
            $resp = Util::getHttpClient()->request(
                'POST',
                $url,
                [
                    RequestOptions::FORM_PARAMS => ['searchkey' => $keyword]
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error('request fail! code: ' . $resp->getStatusCode());
                return null;
            }

            $content = $resp->getBody()->getContents();
            Logger::info('request success! response: ' . substr($content, 0, 100));
            return $this->parseSearchHtml($content);
        } catch (GuzzleException | Exception $e) {
            Logger::error('spiderHtml fail! error: ' . $e->getMessage());
            Logger::error($e->getTraceAsString());
            return null;
        }
    }

    public function novelDir(string $uri): ?array
    {
        $html = $this->spiderHtml($uri);
        if (empty($html)) {
            Logger::error('spider html is empty.');
            return null;
        }
        $info = $this->parseDirHtml($html);
        foreach ($info['chapters'] as $idx => $chapter) {
            $info['chapters'][$idx]['uri'] = $uri . '/' . $chapter['uri'];
        }
        return $info;
    }

    public function novelChapter(string $uri): ?string
    {
        $html = $this->spiderHtml($uri);
        if (empty($html)) {
            Logger::error('spider html is empty.');
            return null;
        }
        return $this->parseChapterHtml($html);
    }

    public function hotList(): ?array
    {
        $html = $this->spiderHtml($this->hotListUri);
        if (empty($html)) {
            Logger::error('spider html is empty.');
            return null;
        }
        return $this->parseHotListHtml($html);
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

        $list = [];

        $divs = $dom->getElementsByTagName('div');
        $novelList = DOMHelp::getFirstNodeByClass($divs, 'novellist');
        $uls = DOMHelp::getFirstNodeByTag($novelList->childNodes, 'ul');
        foreach (DOMHelp::getNodesByTag($uls->childNodes, 'li') as $child) {
            $exp = explode('/', trim($child->textContent));
            $title = $exp[0] ?? '';
            $author = $exp[1] ?? '';

            $a = DOMHelp::getFirstNodeByTag($child->childNodes, 'a');
            if (empty($a)) {
                continue;
            }
            $href = $a->attributes->getNamedItem('href')->textContent;
            $uri = str_replace($this->baseUri, '', $href);

            $list[] = [
                'title' => $title,
                'author' => $author,
                'href' => trim($href, '/'),
                'uri' => trim($uri, '/')
            ];
        }
        return $list;
    }

    /**
     * 解析小说基本信息及章节目录html
     *
     * @param string $html
     * @return array[]|null
     */
    private function parseDirHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $info = ['chapters' => []];
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

        // 章节名称及链接
        $list = $dom->getElementById('list');
        if (empty($list)) {
            Logger::error('getElementById list error. html: ' . $html);
            return null;
        }
        $seq = 0;
        $dl = DOMHelp::getFirstNodeByTag($list->childNodes, 'dl');
        foreach (DOMHelp::getNodesByTag($dl->childNodes, 'dd') as $child) {
            $seq++;
            $title = trim($child->textContent);
            $uri = '';
            $a = DOMHelp::getFirstNodeByTag($child->childNodes, 'a');
            if (!empty($a)) {
                $uri = str_replace($this->baseUri, '', trim($a->attributes->getNamedItem('href')->textContent));
            }
            $info['chapters'][$seq] = [
                'seq' => $seq,
                'title' => $title,
                'uri' => trim($uri, '/')
            ];
        }

        return $info;
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

        $content = $dom->getElementById('content');
        if (empty($content)) {
            return null;
        }

        $lines = [];
        for ($i = 0, $n = $content->childNodes->count(); $i < $n; $i++) {
            $child = $content->childNodes->item($i);
            $line = trim($child->textContent);
            if (empty($line)) {
                continue;
            }
            $lines[] = str_replace([" ", "﻿"], '', $line);
        }
        return implode("\n", $lines);
    }

    /**
     * 解析搜索页html
     *
     * @param string $html
     * @return array|null
     */
    private function parseSearchHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            Logger::error('load html error.');
            return null;
        }
        $list = [];

        $trs = $dom->getElementsByTagName('tr');
        for ($i = 0, $n = $trs->count(); $i < $n; $i++) {
            $tr = $trs->item($i);
            $align = $tr->attributes->getNamedItem('align');
            if (empty($align) || trim($align->textContent) != 'center') {
                continue;
            }
            $info = [];
            foreach (DOMHelp::getNodesByTag($tr->childNodes, 'td') as $idx => $child) {
                if ($idx == 0) {
                    // 书名及链接
                    $info['title'] = trim($child->textContent);
                    $a = DOMHelp::getFirstNodeByTag($child->childNodes, 'a');
                    if (empty($a)) {
                        continue;
                    }
                    $info['uri'] = trim($a->attributes->getNamedItem('href')->textContent, '/');
                    $info['href'] = $this->baseUri . $info['uri'];
                } elseif ($idx == 1) {
                    // 最新章节及链接
                    $info['latest_chapter_name'] = trim($child->textContent);
                    $span = DOMHelp::getFirstNodeByTag($child->childNodes, 'span');
                    if (empty($span)) {
                        continue;
                    }
                    $a = DOMHelp::getFirstNodeByTag($span->childNodes, 'a');
                    if (empty($a)) {
                        continue;
                    }
                    $info['latest_chapter_url'] = trim($a->attributes->getNamedItem('href')->textContent, '/');
                    if (!str_starts_with($info['latest_chapter_url'], 'http')) {
                        $info['latest_chapter_url'] = $this->baseUri . $info['latest_chapter_url'];
                    }
                } elseif ($idx == 2) {
                    // 作者
                    $info['author'] = trim($child->textContent);
                    break;
                }
            }

            if (!empty($info)) {
                $list[] = $info;
            }
        }
        return $list;
    }
}
