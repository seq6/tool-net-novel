<?php

namespace App\Http\Controllers;

use App\Model\Chapter;
use App\Model\Novel;
use App\Service\ApiHelper;
use App\Service\ErrorCode;
use App\Service\Logger;
use App\Service\Novel\NovelSiteFactory;
use App\Service\Novel\NovelStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 小说
 *
 * Class NovelController
 * @package App\Http\Controllers
 */
class NovelController extends Controller
{
    /**
     * 搜索
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function search(Request $req)
    {
        $keyword = trim($req->get('keyword'));
        $site = trim($req->get('site'));
        if (empty($keyword) || empty($site)) {
            Logger::error('params error!');
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID, 'empty params');
        }

        // 获取网站实例
        $service = NovelSiteFactory::getService($site);
        if (empty($service)) {
            Logger::error('error site: ' . $site);
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID, 'error site');
        }

        // 获取搜索结果
        $results = $service->search($keyword);
        if (is_null($results)) {
            Logger::error('search is no results.');
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }

        // 查询已收藏的小说
        $existNovels = Novel::existBatch($site, array_column($results, 'href'));

        foreach ($results as &$r) {
            $r['uri'] = $r['href'];
            $r['href'] = $service->baseUri . $r['href'];
            $r['latest_chapter_url'] = $service->baseUri . $r['latest_chapter_url'];
            $eKey = $site . '-' . $r['uri'];
            $r['is_collect'] = isset($existNovels[$eKey]) ? 1 : 0;
        }
        return ApiHelper::apiSuccess(['list' => $results, 'site' => $site]);
    }

    /**
     * 收藏
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function collect(Request $req)
    {
        $uri = trim($req->get('uri'));
        $site = trim($req->get('site'));
        if (empty($uri) || empty($site)) {
            Logger::error('params error!');
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID, 'empty params');
        }
        $uri = trim($uri, '/');

        // 该小说已被收藏
        $novel = Novel::getByUri($site, $uri);
        if (!empty($novel)) {
            return ApiHelper::apiSuccess();
        }

        // 获取网站实例
        $service = NovelSiteFactory::getService($site);
        if (empty($service)) {
            Logger::error('error site: ' . $site);
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID, 'error site');
        }

        // 获取小说基本信息
        $novel = $service->novelDir($uri);
        if (empty($novel)) {
            Logger::error('error uri: ' . $uri);
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }

        // 添加到数据库
        $id = Novel::add($site, $uri, $novel);
        Chapter::addBatch($id, $novel['chapters']);
        Logger::info('insert success. id=' . $id);

        return ApiHelper::apiSuccess();
    }

    /**
     * 书架
     *
     * @return JsonResponse
     */
    public function library()
    {
        $novels = Novel::getAllNovel();
        $process = Chapter::getSyncProcess();
        foreach ($novels as &$novel) {
            $service = NovelSiteFactory::getService($novel['site']);
            $novel['cover'] = $service->baseUri . $novel['cover'];
            $novel['uri'] = $service->baseUri . $novel['uri'];
            $novel['done_chapter'] = $process[$novel['id']] ?? 0;
        }
        return ApiHelper::apiSuccess(['list' => $novels]);
    }

    /**
     * 热门列表
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function hotlist(Request $req)
    {
        $site = trim($req->get('site'));
        if (empty($site)) {
            Logger::error('params error!');
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID, 'empty params');
        }

        // 获取网站实例
        $service = NovelSiteFactory::getService($site);
        if (empty($service)) {
            Logger::error('error site: ' . $site);
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID, 'error site');
        }

        $results = $service->hotList();
        if (is_null($results)) {
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }

        // 查询已收藏的小说
        $existNovels = Novel::existBatch($site, array_column($results, 'uri'));

        foreach ($results as &$r) {
            $r['href'] = $service->baseUri . $r['uri'];
            $eKey = $site . '-' . $r['uri'];
            $r['is_collect'] = isset($existNovels[$eKey]) ? 1 : 0;
            if (isset($r['latest_chapter_url'])) {
                $r['latest_chapter_url'] = $service->baseUri . $r['latest_chapter_url'];
            }
        }
        return ApiHelper::apiSuccess(['list' => $results]);
    }

    /**
     * 同步小说信息
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function sync(Request $req)
    {
        $id = intval($req->get('novel_id'));
        if ($id <= 0) {
            Logger::error('error id: ' . $id);
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 查询本地数据库小说基本信息
        $novel = Novel::getById($id);
        if (empty($novel)) {
            Logger::error('error id: ' . $id);
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 查询网站小说信息
        $service = NovelSiteFactory::getService($novel['site']);
        if (is_null($service)) {
            Logger::error('error site: ' . $novel['site']);
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }
        $latestNovel = $service->novelDir($novel['uri']);
        if (is_null($latestNovel)) {
            Logger::error('error request');
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }

        // 更新基本信息
        Novel::updateInfo(
            $id,
            [
                'title' => $latestNovel['title'],
                'intro' => $latestNovel['intro'],
                'cover' => $latestNovel['cover'],
                'category' => $latestNovel['category'],
                'author' => $latestNovel['author'],
                'chapter' => count($latestNovel['chapters'])
            ]
        );

        // 添加新增章节
        $chapterSeq = Chapter::getSeq($id);
        $addChapter = array_diff_key($latestNovel['chapters'], $chapterSeq);
        Chapter::addBatch($id, $addChapter);

        return ApiHelper::apiSuccess(['add_chapter' => $addChapter]);
    }

    /**
     * 同步进度
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function syncProcess(Request $req)
    {
        $id = intval($req->get('novel_id'));
        if ($id <= 0) {
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 查询小说基本信息
        $novel = Novel::getById($id);
        if (empty($novel)) {
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 已同步的章节数量
        $fileCount = NovelStorage::countFile($id);

        return ApiHelper::apiSuccess(['total' => intval($novel['chapter']), 'done' => $fileCount]);
    }

    /**
     * 打包下载
     *
     * @param Request $req
     * @return BinaryFileResponse | JsonResponse
     */
    public function downloadZip(Request $req)
    {
        $id = intval($req->get('novel_id'));
        if ($id <= 0) {
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 查询小说基本信息
        $novel = Novel::getById($id);
        if (empty($novel)) {
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 生成压缩文档
        $zipPath = NovelStorage::zipAllChapter($id);
        if (empty($zipPath)) {
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }
        return response()->download($zipPath, $novel['title'] . '.zip');
    }

    /**
     * 合成为一个文件并下载
     *
     * @param Request $req
     * @return JsonResponse|BinaryFileResponse
     */
    public function downloadTxt(Request $req)
    {
        $id = intval($req->get('novel_id'));
        if ($id <= 0) {
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        // 查询小说基本信息
        $novel = Novel::getById($id);
        if (empty($novel)) {
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        $filePath = NovelStorage::mergeAllChapter($id);
        if (empty($filePath)) {
            return ApiHelper::apiFail(ErrorCode::SYSTEM_ERROR);
        }
        return response()->download($filePath, $novel['title'] . '.txt');
    }

    /**
     * 删除
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function delete(Request $req)
    {
        $id = intval($req->get('novel_id'));
        if ($id <= 0) {
            Logger::info('novel_id is ' . $id);
            return ApiHelper::apiFail(ErrorCode::PARAMS_INVALID);
        }

        Novel::del($id);
        Chapter::del($id);
        NovelStorage::delDirPath($id);
        Logger::info('delete novel id=' . $id);

        return ApiHelper::apiSuccess();
    }

    /**
     * 查询小说是否已被收藏
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function isCollectedBatch(Request $req)
    {
        $site = trim($req->get('site'));
        $uris = explode(';', $req->get('uris'));
        return ApiHelper::apiSuccess(['collected' => Novel::existBatch($site, $uris)]);
    }
}
