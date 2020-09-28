<?php

namespace Tests\Unit;

use App\Service\Logger;
use App\Service\Util;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BxwxTest extends TestCase
{
    public function testDownloadHtml()
    {
        $client = Util::getHttpClient();
        $cookie = '';
        foreach (
            [
                'http://www.bxwx666.org/ph/1.htm' => 'example/bxwx_rank_example.html',
                'http://www.bxwx666.org/txt/216425/' => 'example/bxwx_novel_example.html',
                'http://www.bxwx666.org/txt/216425/875719.htm' => 'example/bxwx_chapter_example.html',
                //'http://www.bxwx666.org/search.aspx?bookname=' . $keyword => 'example/bxwx_search_example.html'
            ] as $url => $file
        ) {
            $resp = $client->request('GET', $url);
            if ($resp->getStatusCode() != 200) {
                echo 'fail! ' . $url;
                $this->assertTrue(false);
                continue;
            }
            dd($resp->getHeaders());

            $cookie = $resp->getHeader('Set-Cookie');
            $cookie = end($cookie);
            Logger::info('download success. url=' . $url);
            Logger::info('cookie: ' . $cookie);
            Storage::put($file, $resp->getBody()->getContents());
        }

        // todo search
        /*$keyword = urlencode(mb_convert_encoding('赘婿', 'GB2312'));
        $searchUrl = 'http://www.bxwx666.org/search.aspx?bookname=' . $keyword;
        //$cookieJar = new CookieJar(false, [SetCookie::fromString(end($cookie))]);
        $resp = $client->request('GET', $searchUrl, [
            RequestOptions::HEADERS => ['Cookie' => end($cookie)]
        ]);
        if ($resp->getStatusCode() != 200) {
            echo 'fail! ' . $url;
            $this->assertTrue(false);
        }
        Storage::put($file, $resp->getBody()->getContents());*/

        $this->assertTrue(true);
    }

    public function testNothing()
    {
        $baseUri = 'www.bxwx666.org';
        $date = date('Y-m-d_H:i:s').':'.rand(100, 999);
        $randNum = rand(1,1000);
        echo sprintf('%s_%s_%s', $baseUri, $date, $randNum);
        $this->assertTrue(true);
    }
}
