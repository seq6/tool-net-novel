<?php

namespace Tests\Unit;

use App\Service\Logger;
use App\Service\Novel\XbqgService;
use Illuminate\Support\Facades\Storage;
use ReflectionException;
use ReflectionMethod;
use Tests\TestCase;
use Throwable;

/**
 * <新笔趣阁>测试案例
 *
 * Class XbqgTest
 * @package Tests\Unit
 */
class XbqgTest extends TestCase
{
    /**
     * 解析搜索结果html
     */
    public function testParseSearchHtml()
    {
        try {
            $html = Storage::get('example/xbqg_search_example.html');
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
     * 测试解析排行榜html
     */
    public function testParseHotListHtml()
    {
        try {
            $html = Storage::get('example/xbqg_rank_example.html');
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
            $html = Storage::get('example/xbqg_novel_example.html');
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
            $html = Storage::get('example/xbqg_chapter_example.html');
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
        $method = new ReflectionMethod(XbqgService::class, $methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(new XbqgService(), $params);
    }
}
