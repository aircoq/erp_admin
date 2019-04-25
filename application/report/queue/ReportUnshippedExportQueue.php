<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ReportUnshippedService;


class ReportUnshippedExportQueue extends SwooleQueueJob
//class ReportUnshippedExportQueue
{
    public function getName(): string
    {
        return "未发货记录导出队列";
    }

    public function getDesc(): string
    {
        return "未发货记录导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public function execute()
    {
        try {
            $data=$this->params;
            $serv = new ReportUnshippedService();
            $serv->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_unshipped:report_unshipped',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}