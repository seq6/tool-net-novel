<?php


namespace App\Console;

use App\Model\Chapter;
use App\Model\Novel;
use App\Service\Logger;
use App\Service\Novel\NovelSiteFactory;
use App\Service\Novel\NovelStorage;
use Illuminate\Console\Command;

class SyncChapter extends Command
{
    protected $name = 'sync:chapter';

    protected $description = 'sync novel chapter schedule';

    public function handle()
    {
        Logger::info('sync:chapter is running!');

        $start = 0;
        while (true) {
            $chapters = Chapter::getInitChapters($start);
            if (empty($chapters)) {
                break;
            }

            $novelIds = array_unique(array_column($chapters, 'novel_id'));
            $novels = Novel::getBatchByIds($novelIds, ['id', 'site']);

            foreach ($chapters as $chapter) {
                if (!isset($novels[$chapter['novel_id']])) {
                    continue;
                }
                $novel = $novels[$chapter['novel_id']];
                $service = NovelSiteFactory::getService($novel['site']);
                $data = $service->novelChapter($chapter['uri']);
                if (is_null($data)) {
                    continue;
                }
                NovelStorage::updateText($chapter, $data);
                Chapter::updateSyncTime($chapter['id'], time());
                Logger::info(sprintf('sync chapter [%d. %s] is done.', $chapter['id'], $chapter['title']));
                usleep(500);    // 防止请求过于频繁被拦截
            }
            $start = end($chapters)['id'];
        }
    }
}
