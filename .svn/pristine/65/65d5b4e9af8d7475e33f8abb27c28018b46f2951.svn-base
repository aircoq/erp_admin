<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\StatisticMessage;
use app\report\service\StatisticShelf;
use app\report\service\StatisticTime;


class PublishbyTimeExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "SPU上架时间统计导出队列";
    }

    public function getDesc(): string
    {
        return "SPU上架时间统计导出队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public function execute()
    {
        try {
            $serv = new StatisticTime();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export:publish_by_times',
                'error_'.time(),
                $ex->getMessage());
            return  $ex->getMessage();
        }
        return true;
    }
}