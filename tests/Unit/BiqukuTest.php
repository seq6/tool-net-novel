<?php

namespace Tests\Unit;

use App\Service\Logger;
use App\Service\Novel\BiqukuService;
use App\Service\Util;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use ReflectionException;
use ReflectionMethod;
use Tests\TestCase;
use Throwable;

/**
 * <笔趣阁>测试案例
 *
 * Class BiqukuTest
 * @package Tests\Unit
 */
class BiqukuTest extends TestCase
{
    /**
     * 下载网页html
     */
    public function testDownloadHtml()
    {
        try {
            $client = Util::getHttpClient();
            foreach (
                [
                    'http://www.biquku.la/0/165/' => 'example/biquku_novel_example.html',
                    'http://www.biquku.la/0/165/152692.html' => 'example/biquku_chapter_example.html',
                    'http://www.biquku.la/paihangbang/' => 'example/biquku_rank_example.html',
                ] as $url => $file
            ) {
                $resp = $client->request('GET', $url);
                if ($resp->getStatusCode() != 200) {
                    Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                    $this->assertTrue(false);
                    continue;
                }
                Storage::put($file, $resp->getBody()->getContents());
            }

            // 搜索页
            $resp = $client->request(
                'POST',
                'http://www.biquku.la/modules/article/search.php',
                [
                    RequestOptions::FORM_PARAMS => [
                        'searchkey' => '赘婿'
                    ]
                ]
            );
            if ($resp->getStatusCode() != 200) {
                Logger::error(sprintf('request %s fail! http code=%d', $url, $resp->getStatusCode()));
                $this->assertTrue(false);
            } else {
                Storage::put('example/biquku_search_example.html', $resp->getBody()->getContents());
                $this->assertTrue(true);
            }
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
            $html = Storage::get('example/biquku_search_example.html');
            $info = $this->testPrivateMethod('parseSearchHtml', [$html]);
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
     * 解析热门榜单html
     */
    public function testParseHotListHtml()
    {
        try {
            $html = Storage::get('example/biquku_rank_example.html');
            $info = $this->testPrivateMethod('parseHotListHtml', [$html]);
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
     * 解析小说基本信息及章节目录html
     */
    public function testParseDirHtml()
    {
        try {
            $html = Storage::get('example/biquku_novel_example.html');
            $info = $this->testPrivateMethod('parseDirHtml', [$html]);
            Logger::info(
                'testParseDirHtml success. result: ' . json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testParseDirHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
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
            $html = Storage::get('example/biquku_chapter_example.html');
            $info = $this->testPrivateMethod('parseChapterHtml', [$html]);
            Logger::info('testParseChapterHtml success. result: ' . $info);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            Logger::error(
                sprintf('testParseChapterHtml fail! error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString())
            );
            $this->assertTrue(false);
        }
    }

    /**
     * 测试私有方法
     *
     * @param string $methodName
     * @param array $params
     * @return mixed
     * @throws ReflectionException
     */
    private function testPrivateMethod(string $methodName, array $params = [])
    {
        $method = new ReflectionMethod(BiqukuService::class, $methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(new BiqukuService(), $params);
    }
}
