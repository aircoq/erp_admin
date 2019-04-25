<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ReportUnpackedService;


//class ReportUnpackedExportQueue extends SwooleQueueJob
class ReportUnpackedExportQueue
{
    public function getName(): string
    {
        return "未拆包记录导出队列";
    }

    public function getDesc(): string
    {
        return "未拆包记录导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public function execute($data)
    {
        try {
            $this->params=$data;
            $serv = new ReportUnpackedService();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_unpacked:report_unpacked',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}