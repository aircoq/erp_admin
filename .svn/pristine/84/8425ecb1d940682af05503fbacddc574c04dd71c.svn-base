<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ReportShippedService;


class ReportShippedExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "已发货记录导出队列";
    }

    public function getDesc(): string
    {
        return "已发货记录导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $serv = new ReportShippedService();
            $serv->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_shipped:report_shipped',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}