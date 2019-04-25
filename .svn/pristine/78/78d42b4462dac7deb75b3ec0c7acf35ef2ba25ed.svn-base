<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\StatisticMessage;


class CustomerMessageExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "客服业绩统计导出队列";
    }

    public function getDesc(): string
    {
        return "客服业绩统计导出队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public function execute()
    {
        try {
            $serv = new StatisticMessage();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export:customer_message',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}