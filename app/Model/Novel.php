<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * 小说
 *
 * Class Novel
 * @package App\Model
 */
class Novel extends Model
{
    protected $table = 'novel';

    const STATUS_CONTINUE = 1;  // 连载中
    const STATUS_FINISHED = 2;  // 已完结

    /**
     * 根据id查询
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $novel = self::where('id', $id)->first();
        return empty($novel) ? null : $novel->toArray();
    }

    /**
     * 根据uri查询
     *
     * @param string $site
     * @param string $uri
     * @return array|null
     */
    public static function getByUri(string $site, string $uri): ?array
    {
        $novel = self::where(
            ['site' => $site, 'uri' => $uri]
        )->first();
        return empty($novel) ? null : $novel->toArray();
    }

    /**
     * 批量查询
     *
     * @param array $ids
     * @param array|string[] $cols
     * @return array|null
     */
    public static function getBatchByIds(array $ids, array $cols = ['*']): ?array
    {
        if (empty($ids)) {
            return [];
        }

        if (!in_array('*', $cols) && !in_array('id', $cols)) {
            $cols[] = 'id';
        }

        $dbNovels = self::whereIn('id', $ids)->get($cols)->toArray();
        $novels = [];
        foreach ($dbNovels as $n) {
            $novels[$n['id']] = $n;
        }
        return $novels;
    }

    /**
     * 新增
     *
     * @param string $site
     * @param string $uri
     * @param array $novel
     * @return int
     */
    public static function add(string $site, string $uri, array $novel): int
    {
        return self::insertGetId(
            [
                'site' => $site,
                'uri' => $uri,
                'title' => $novel['title'],
                'category' => $novel['category'],
                'author' => $novel['author'],
                'cover' => $novel['cover'],
                'intro' => $novel['intro'],
                'chapter' => count($novel['chapters'])
            ]
        );
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $update
     */
    public static function updateInfo(int $id, array $update = [])
    {
        self::where('id', $id)->update($update);
    }

    /**
     * 删除
     *
     * @param int $id
     */
    public static function del(int $id)
    {
        self::where('id', $id)->delete();
    }

    /**
     * 用于判断小说是否已收藏
     *
     * @param string $site
     * @param array $uris
     * @return array
     */
    public static function existBatch(string $site, array $uris): array
    {
        $novels = self::where('site', $site)
            ->whereIn('uri', $uris)
            ->get(['id', 'site', 'uri'])->toArray();
        $list = [];
        foreach ($novels as $novel) {
            $key = $novel['site'] . '-' . $novel['uri'];
            $list[$key] = $novel['id'];
        }
        return $list;
    }

    /**
     * 获取所有已收藏小说
     *
     * @param array|string[] $cols
     * @return array
     */
    public static function getAllNovel(array $cols = ['*']): array
    {
        return self::get($cols)->toArray();
    }
}
