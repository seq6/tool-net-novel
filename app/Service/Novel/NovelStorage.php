<?php

namespace App\Service\Novel;

use App\Model\Chapter;
use App\Service\Logger;
use Generator;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * 小说存储
 *
 * Class NovelStorage
 * @package App\Service\Novel
 */
class NovelStorage
{
    /**
     * 删除目录
     *
     * @param int $novelId
     * @return bool
     */
    public static function delDirPath(int $novelId): bool
    {
        return Storage::deleteDirectory('novel/' . $novelId);
    }

    /**
     * 更新文本信息
     *
     * @param array $chapter
     * @param string $data
     * @return bool
     */
    public static function updateText(array $chapter, string $data): bool
    {
        $filePath = 'novel/' . $chapter['novel_id'] . '/' . $chapter['seq'];
        $lines = explode("\n", $data);
        $content = $chapter['title'] . "\n\n\t" . implode("\n\t", $lines) . "\n";
        return Storage::put($filePath, $content);
    }

    /**
     * 集合所有章节至压缩文件
     *
     * @param int $novelId
     * @return string|null
     */
    public static function zipAllChapter(int $novelId): ?string
    {
        // 创建zip归档
        $zipPath = storage_path('novel/' . $novelId . '.zip');
        $zip = new ZipArchive();
        $resCode = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($resCode != true) {
            Logger::error('create zip fail! res code: ' . $resCode);
            return null;
        }

        // 添加文本文件
        $seq2Title = Chapter::getSeq2Title($novelId);
        foreach (Storage::files('novel/' . $novelId) as $file) {
            $exp = explode('/', $file);
            $seq = intval(end($exp));
            $title = $seq2Title[$seq] ?? '-';
            $realPath = storage_path() . '/' . $file;
            $localName = sprintf('[%d]%s.txt', $seq, $title);
            $zip->addFile($realPath, $localName);
        }
        $zip->close();
        return $zipPath;
    }

    /**
     * 合并所有章节至单一文件
     *
     * @param int $novelId
     * @return string|null
     */
    public static function mergeAllChapter(int $novelId): ?string
    {
        $oneFile = 'novel/' . $novelId . '.txt';
        Storage::put($oneFile, '');

        $fileList = [];
        foreach (Storage::files('novel/' . $novelId) as $file) {
            $exp = explode('/', $file);
            $seq = intval(end($exp));
            $fileList[$seq] = $file;
        }
        ksort($fileList);

        foreach ($fileList as $file) {
            Storage::append($oneFile, Storage::get($file));
        }
        return storage_path($oneFile);
    }

    /**
     * 获取文本文件数量
     *
     * @param int $novelId
     * @return int
     */
    public static function countFile(int $novelId): int
    {
        $files = Storage::allFiles('novel/' . $novelId);
        return count($files);
    }
}
