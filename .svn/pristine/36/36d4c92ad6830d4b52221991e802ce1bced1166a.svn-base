<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ReportShortageService;


class ReportShortageExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "缺货记录导出队列";
    }

    public function getDesc(): string
    {
        return "缺货记录导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public function execute()
    {
        try {
           $data= $this->params;
            $serv = new ReportShortageService();
            $serv->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_shortage:report_shortage',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}