<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\StatisticMessage;
use app\report\service\StatisticShelf;


class PublishbyShelfExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "计刊登上架SPU导出队列";
    }

    public function getDesc(): string
    {
        return "计刊登上架SPU导出队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public function execute()
    {
        try {
            $serv = new StatisticShelf();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export:publish_by_shelf',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}