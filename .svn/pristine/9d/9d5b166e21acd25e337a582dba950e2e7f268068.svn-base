<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\SaleRefundService;


class SaleRefundExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "销退汇总导出队列";
    }

    public function getDesc(): string
    {
        return "销退汇总导出队列";
    }

    public function getAuthor(): string
    {
        return "laiyongfeng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $serv = new SaleRefundService();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}