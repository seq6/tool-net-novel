<?php

namespace App\Service\Novel;

use App\Service\DOMHelp;
use DateTime;
use DOMDocument;

/**
 * 笔下文学
 *
 * Class BxwxService
 * @package App\Service\Novel
 */
class BxwxService extends NovelBaseService
{
    public $baseUri = 'http://www.bxwx666.org/';

    private $cuid = '';

    public function search(string $keyword): ?array
    {
        // TODO: Implement search() method.
    }

    public function novelDir(string $uri): ?array
    {
        // TODO: Implement novelDir() method.
    }

    public function novelChapter(string $uri): ?string
    {
        // TODO: Implement novelChapter() method.
    }

    public function hotList(): ?array
    {
        // TODO: Implement hotList() method.
    }

    /**
     * 解析搜索页面html
     *
     * @param string $html
     * @return array|null
     */
    public function parseSearchHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $content = $dom->getElementById('newscontent');
        if (empty($content)) {
            echo "ok1 \n";
            return null;
        }
        $div = DOMHelp::getFirstNodeByClass($content->childNodes, 'l');
        if (empty($div)) {
            echo "ok2 \n";
            return null;
        }
        $ul = DOMHelp::getFirstNodeByTag($div->childNodes, 'ul');
        if (empty($ul)) {
            echo "ok3 \n";
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
     * 解析热门榜单html
     *
     * @param string $html
     * @return array|null
     */
    public function parseHotListHtml(string $html): ?array
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
    public function parseNovelBaseHtml(string $html): ?array
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
    public function parseNovelChaptersHtml(string $html): ?array
    {
        $chapters = [];
        $html = str_replace(['<dd>', '</dd>', '<a href="http://www.bxwx666.org/'], '', $html);
        $arr = explode('</a>', $html);
        foreach ($arr as $idx => $line) {
            $exp = explode('">', $line);
            if (count($exp) != 2) {
                continue;
            }
            $chapters[] = [
                'seq' => $idx + 1,
                'title' => trim($exp[0]),
                'uri' => trim($exp[1])
            ];
        }
        return $chapters;
    }

    private function getCuid()
    {
        if (empty($this->cuid)) {
            $this->cuid = sprintf(
                '%s_%s_%s',
                $this->baseUri,
                (new DateTime())->format('Y-m-d_H:i:s:u'),
                rand(1, 1000)
            );
        }
        return $this->cuid;
    }
}
