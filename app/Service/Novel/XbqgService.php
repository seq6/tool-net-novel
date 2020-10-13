<?php

namespace App\Service\Novel;

use App\Service\DOMHelp;
use App\Service\Logger;
use DOMDocument;

/**
 * 新笔趣阁
 *
 * Class XbqgService
 * @package App\Service\Novel
 */
class XbqgService extends NovelBaseService
{
    public $baseUri = 'https://www.xsbiquge.com/';

    private $searchUri = 'search.php';
    private $hotListUri = 'xbqgph.html';

    public function search(string $keyword): ?array
    {
        $html = $this->spiderHtml($this->searchUri, ['keyword' => $keyword]);
        if (empty($html)) {
            Logger::error('spider html is empty.');
            return null;
        }
        return $this->parseSearchHtml($html);
    }

    public function novelDir(string $uri): ?array
    {
        $html = $this->spiderHtml($uri);
        if (empty($html)) {
            Logger::error('spider html is empty.');
            return null;
        }
        return $this->parseDirHtml($html);
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

        $main = $dom->getElementById('main');
        $ul = $main->getElementsByTagName('ul')->item(0);

        $list = [];
        for ($i = 0, $n = $ul->childNodes->count(); $i < $n; $i++) {
            $li = $ul->childNodes->item($i);
            if (empty(trim($li->textContent))) {
                continue;
            }
            $item = [];
            for ($j = 0, $n1 = $li->childNodes->count(); $j < $n1; $j++) {
                $span = $li->childNodes->item($j);
                if ($span->nodeName != 'span') {
                    continue;
                }
                switch ($span->attributes->getNamedItem('class')->textContent) {
                    case 's1':
                        $item['category'] = trim($span->textContent);
                        $item['category'] = trim($item['category'], '[]');
                        break;
                    case 's2':
                        $item['title'] = trim($span->textContent);
                        $a = DOMHelp::getFirstNodeByTag($span->childNodes, 'a');
                        if (!empty($a)) {
                            $uri = $a->attributes->getNamedItem('href')->textContent;
                            $item['uri'] = trim($uri, '/');
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
                    case 's6':
                        if (trim($span->textContent) == '连载中') {
                            $item['status'] = 1;
                        } else {
                            $item['status'] = 2;
                        }
                        break;
                }
            }
            if (!isset($item['uri'])) {
                continue;
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 解析小说基本信息及章节目录html
     *
     * @param string $html
     * @return array|null
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

        // 搜索结果列表
        $targetTag = 'div';
        $targetClass = 'result-game-item';
        $nodeList = $dom->getElementsByTagName($targetTag);
        foreach (
            DOMHelp::getNodesByClass($nodeList, $targetClass) as $node
        ) {
            $picNode = DOMHelp::getFirstNodeByClass($node->childNodes, 'result-game-item-pic');
            $detailNode = DOMHelp::getFirstNodeByClass($node->childNodes, 'result-game-item-detail');
            if (empty($picNode) || empty($detailNode)) {
                continue;
            }
            $info = [];

            // 封面
            $a = DOMHelp::getFirstNodeByTag($picNode->childNodes, 'a');
            $img = DOMHelp::getFirstNodeByTag($a->childNodes, 'img');
            $item = $img->attributes->getNamedItem('src');
            $info['cover'] = str_replace($this->baseUri, '', $item->textContent);

            // 标题
            $titleNode = DOMHelp::getFirstNodeByClass($detailNode->childNodes, 'result-game-item-title');
            if (empty($titleNode) || empty($titleNode->attributes)) {
                continue;
            }
            $info['title'] = str_replace(' ', '', trim($titleNode->textContent));

            // 链接
            $a = DOMHelp::getFirstNodeByTag($titleNode->childNodes, 'a');
            if (empty($a) || empty($a->attributes)) {
                continue;
            }
            $item = $a->attributes->getNamedItem('href');
            if (empty($item)) {
                continue;
            }
            $info['href'] = trim($item->textContent, '/');
            $info['uri'] = str_replace($this->baseUri, '', $info['href']);

            // 简介
            $descNode = DOMHelp::getFirstNodeByClass($detailNode->childNodes, 'result-game-item-desc');
            if (empty($descNode)) {
                continue;
            }
            $info['intro'] = str_replace([' ', '　', "\n"], '', trim($descNode->textContent));

            // 其它信息
            $infoNodes = DOMHelp::getFirstNodeByClass($detailNode->childNodes, 'result-game-item-info');
            if (empty($infoNodes)) {
                continue;
            }
            foreach (DOMHelp::getNodesByClass($infoNodes->childNodes, 'result-game-item-info-tag') as $infoTagNode) {
                $infoTag = str_replace(["\n", "\r", ' '], '', $infoTagNode->textContent);
                if (str_starts_with($infoTag, '作者')) {
                    $info['author'] = mb_substr($infoTag, mb_strlen('作者：'));
                } elseif (str_starts_with($infoTag, '类型')) {
                    $info['category'] = mb_substr($infoTag, mb_strlen('类型：'));
                } elseif (str_starts_with($infoTag, '更新时间')) {
                    $info['updated_at'] = mb_substr($infoTag, mb_strlen('更新时间：'));
                } elseif (str_starts_with($infoTag, '最新章节')) {
                    $info['latest_chapter_name'] = mb_substr($infoTag, mb_strlen('最新章节：'));
                    $a = DOMHelp::getFirstNodeByTag($infoTagNode->childNodes, 'a');
                    $item = $a->attributes->getNamedItem('href');
                    if (empty($item)) {
                        continue;
                    }
                    $info['latest_chapter_url'] = trim($item->textContent, ' /');
                    if (!str_starts_with($info['latest_chapter_url'], 'http')) {
                        $info['latest_chapter_url'] = $this->baseUri . $info['latest_chapter_url'];
                    }
                }
            }

            $list[] = $info;
        }
        return $list;
    }
}
