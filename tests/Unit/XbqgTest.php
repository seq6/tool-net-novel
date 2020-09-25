<?php

namespace Tests\Unit;

use App\Service\Novel\XbqgService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

class XbqgTest extends TestCase
{
    /**
     * 解析搜索结果html
     */
    public function testParseSearchHtml()
    {
        try {
            $html = Storage::get('example/search_example.html');
            $info = (new XbqgService())->parseSearchHtml($html);
            echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->assertTrue(false);
        }
    }

    /**
     * 测试解析排行榜html
     */
    public function testParseHotListHtml()
    {
        try {
            $html = Storage::get('example/ph_example.html');
            $info = (new XbqgService())->parseHotListHtml($html);
            echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->assertTrue(false);
        }
    }

    /**
     * 解析小说基本信息及章节目录html
     */
    public function testParseDirHtml()
    {
        try {
            $html = Storage::get('example/novel_dir_example.html');
            $info = (new XbqgService())->parseDirHtml($html);
            echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->assertTrue(false);
        }
    }

    /**
     * 解析小说正文html
     */
    public function testParseChapterHtml()
    {
        try {
            $html = Storage::get('example/novel_chapter_example.html');
            $info = (new XbqgService())->parseChapterHtml($html);
            echo $info;
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->assertTrue(false);
        }
    }
}
