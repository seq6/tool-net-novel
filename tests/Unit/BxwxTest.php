<?php

namespace Tests\Unit;

use App\Service\Logger;
use App\Service\Novel\BxwxService;
use App\Service\Util;
use DateTime;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;
use Throwable;

/**
 * 笔下文学测试案例
 *
 * Class BxwxTest
 * @package Tests\Unit
 */
class BxwxTest extends TestCase
{
    /**
     * 下载网页html
     */
    public function testDownloadHtml()
    {
        //new ReflectionMethod()

        try {
            $client = Util::getHttpClient();

            // 排行榜
            $url = 'http://www.bxwx666.org/ph/1.htm';
            $file = 'example/bxwx_rank_example.html';
            $resp = $client->request('GET', $url);
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                $this->assertTrue(false);
            }
            Storage::put($file, $resp->getBody()->getContents());

            // cookie
            $setCookie = $resp->getHeader('Set-Cookie');
            $cuid = sprintf('www.bxwx666.org_%s_%d', (new DateTime())->format('Y-m-d_H:i:s:u'), rand(1, 1000));
            $cookie = 'LookNum=3; cuid=' . $cuid . '; ' . $setCookie[0];
            Logger::info('cookie: ' . $cookie);

            // 小说基本信息
            $url = 'http://www.bxwx666.org/txt/216425/';
            $file = 'example/bxwx_novel1_example.html';
            $resp = $client->request('GET', $url, [RequestOptions::HEADERS => ['Cookie' => $cookie]]);
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                $this->assertTrue(false);
            }
            Storage::put($file, $resp->getBody()->getContents());

            // 小说章节列表
            $url = 'http://www.bxwx666.org/ashx/zj.ashx';
            $file = 'example/bxwx_novel2_example.html';
            $resp = $client->request(
                'POST',
                $url,
                [
                    RequestOptions::HEADERS => ['Cookie' => $cookie],
                    RequestOptions::FORM_PARAMS => ['action' => 'GetZj', 'xsid' => 216425]
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                $this->assertTrue(false);
            }
            Storage::put($file, $resp->getBody()->getContents());

            // 章节正文
            $url = 'http://www.bxwx666.org/txt/216425/875719.htm';
            $file = 'example/bxwx_chapter_example.html';
            $resp = $client->request('GET', $url, [RequestOptions::HEADERS => ['Cookie' => $cookie]]);
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                $this->assertTrue(false);
            }
            Storage::put($file, $resp->getBody()->getContents());

            // 搜索
            $keyword = urlencode(mb_convert_encoding('凡人', 'GB2312'));
            $url = 'http://www.bxwx666.org/search.aspx?bookname=' . $keyword;
            $file = 'example/bxwx_search_example.html';
            Logger::info('url: ' . $url);
            $resp = $client->request(
                'GET',
                $url,
                [
                    RequestOptions::HEADERS => [
                        'Cookie' => $cookie,
                        'Referer' => 'http://www.bxwx666.org/ph/1.htm'
                    ]
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                $this->assertTrue(false);
            }
            Storage::put($file, $resp->getBody()->getContents());

            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testDownloadHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            $this->assertTrue(false);
        }
    }

    /**
     * 解析搜索结果html
     */
    public function testParseSearchHtml()
    {
        try {
            $html = Storage::get('example/bxwx_search_example.html');
            $html = mb_convert_encoding($html, 'UTF-8', 'GB2312');
            $info = (new BxwxService())->parseSearchHtml($html);
            Logger::info(
                'testParseSearchHtml success. result: ' . json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testParseSearchHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            $this->assertTrue(false);
        }
    }

    /**
     * 测试解析排行榜html
     */
    public function testParseHotListHtml()
    {
        try {
            $html = Storage::get('example/bxwx_rank_example.html');
            $html = mb_convert_encoding($html, 'UTF-8', 'GB2312');
            $info = (new BxwxService())->parseHotListHtml($html);
            Logger::info(
                'testParseHotListHtml success. result: ' . json_encode(
                    $info,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testParseHotListHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            $this->assertTrue(false);
        }
    }

    /**
     * 解析小说基本信息html
     */
    public function testParseNovelBaseHtml()
    {
        try {
            $html = Storage::get('example/bxwx_novel1_example.html');
            $html = mb_convert_encoding($html, 'UTF-8', 'GB2312');
            $info = (new BxwxService())->parseNovelBaseHtml($html);
            Logger::info(
                'testParseNovelBaseHtml success. result: ' . json_encode(
                    $info,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testParseNovelBaseHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            $this->assertTrue(false);
        }
    }

    /**
     * 解析章节目录html
     */
    public function testParseNovelChaptersHtml()
    {
        try {
            $html = Storage::get('example/bxwx_novel2_example.html');
            $html = mb_convert_encoding($html, 'UTF-8', 'GB2312');
            $info = (new BxwxService())->parseNovelChaptersHtml($html);
            Logger::info(
                'testParseNovelChaptersHtml success. result: ' . json_encode(
                    $info,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf(
                    'testParseNovelChaptersHtml fail! error: %s, trace: %s',
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );
            $this->assertTrue(false);
        }
    }

    /**
     * 解析章节正文html
     */
    public function testParseChapterHtml()
    {
        try {
            $html = Storage::get('example/bxwx_chapter_example.html');
            $html = mb_convert_encoding($html, 'UTF-8', 'GB2312');

            $method = new ReflectionMethod('App\Service\Novel\BxwxService', 'parseChapterHtml');
            $method->setAccessible(true);
            $info = $method->invokeArgs(new BxwxService(), [$html]);

            Logger::info('testParseChapterHtml success. result: ' . $info);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testParseChapterHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            $this->assertTrue(false);
        }
    }
}
