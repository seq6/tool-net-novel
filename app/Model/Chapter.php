<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * 章节
 *
 * Class Chapter
 * @package App\Model
 */
class Chapter extends Model
{
    protected $table = 'chapter';

    /**
     * 批量添加记录
     *
     * @param int $novelId
     * @param array $chapters
     */
    public static function addBatch(int $novelId, array $chapters)
    {
        $values = [];
        foreach ($chapters as $seq => $ch) {
            $values[] = [
                'novel_id' => $novelId,
                'seq' => $seq,
                'title' => $ch['title'],
                'uri' => $ch['uri']
            ];
            // 每2000条记录进行一次插入
            if (count($values) >= 2000) {
                self::insert($values);
                $values = [];
            }
        }
        if (!empty($values)) {
            self::insert($values);
        }
    }

    /**
     * 更新同步时间戳
     *
     * @param int $id
     * @param int $timestamp
     */
    public static function updateSyncTime(int $id, int $timestamp)
    {
        self::where('id', $id)->update(['sync_at' => $timestamp]);
    }

    /**
     * 查询已有的小说章节序号
     *
     * @param int $novelId
     * @return array
     */
    public static function getSeq(int $novelId): array
    {
        $res = self::where('novel_id', $novelId)->get(['seq'])->toArray();
        $seqArr = [];
        foreach ($res as $re) {
            $seqArr[$re['seq']] = $re;
        }
        return $seqArr;
    }

    /**
     * 删除小说章节信息
     *
     * @param int $novelId
     */
    public static function del(int $novelId)
    {
        self::where('novel_id', $novelId)->delete();
    }

    /**
     * 获取未同步的文本章节
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public static function getInitChapters(int $start = 0, int $limit = 20): array
    {
        return self::where('sync_at', 0)->where(
            function ($query) use ($start) {
                if ($start > 0) {
                    $query->where('id', '<', $start);
                }
            }
        )->orderBy('id', 'desc')->limit($limit)->get()->toArray();
    }

    /**
     * 获取所有小说同步进度
     *
     * @return array
     */
    public static function getSyncProcess()
    {
        $syncProcess = self::selectRaw("novel_id, count(1) as done")
            ->where('sync_at', '>', 0)
            ->groupBy('novel_id')
            ->get()->toArray();
        $list = [];
        foreach ($syncProcess as $s) {
            $list[$s['novel_id']] = intval($s['done']);
        }
        return $list;
    }

    /**
     * 获取小说章节序号和标题的对应关系
     *
     * @param int $novelId
     * @return array
     */
    public static function getSeq2Title(int $novelId): array
    {
        $chapters = self::where('novel_id', $novelId)->get(['seq', 'title'])->toArray();
        $list = [];
        foreach ($chapters as $ch) {
            $list[$ch['seq']] = $ch['title'];
        }
        return $list;
    }
}
