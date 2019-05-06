<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\LazadaAccountReportService;


class LazadaAccountReportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "lazada业绩报表导出队列";
    }

    public function getDesc(): string
    {
        return "lazada业绩报表导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $this->params;
            $serv = new LazadaAccountReportService();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}