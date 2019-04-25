<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\AmazonAccountMonitorService;


class AmazonAccountMonitorExportQueue extends SwooleQueueJob

{
    public function getName(): string
    {
        return "亚马逊账号监控导出队列";
    }

    public function getDesc(): string
    {
        return "亚马逊账号监控导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        try {
            $serv = new AmazonAccountMonitorService();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_amazon',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}