<?php


namespace Tests\Unit;


use App\Service\Novel\BiqukuService;
use App\Service\Util;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

class BiqukuTest extends TestCase
{
    public function testDownloadHtml()
    {
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
                echo 'fail! ' . $url;
                $this->assertTrue(false);
                continue;
            }
            Storage::put($file, $resp->getBody()->getContents());
        }

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
            echo 'fail! ' . $url;
            $this->assertTrue(false);
        } else {
            Storage::put('example/biquku_search_example.html', $resp->getBody()->getContents());
        }
        $this->assertTrue(true);
    }

    public function testParseHotListHtml()
    {
        try {
            $html = Storage::get('example/biquku_rank_example.html');
            $info = (new BiqukuService())->parseHotListHtml($html);
            echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->assertTrue(false);
        }
    }

    public function testParseDirHtml()
    {
        try {
            $html = Storage::get('example/biquku_novel_example.html');
            $info = (new BiqukuService())->parseDirHtml($html);
            echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            $this->assertTrue(false);
        }
    }

    public function testParseChapterHtml()
    {
        try {
            $html = Storage::get('example/biquku_chapter_example.html');
            $info = (new BiqukuService())->parseChapterHtml($html);
            echo $info;
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->assertTrue(false);
        }
    }

    public function testParseSearchHtml()
    {
        try {
            $html = Storage::get('example/biquku_search_example.html');
            $info = (new BiqukuService())->parseSearchHtml($html);
            echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            $this->assertTrue(false);
        }
    }
}
