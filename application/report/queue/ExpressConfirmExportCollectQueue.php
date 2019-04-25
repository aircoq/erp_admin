<?php

namespace app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ExpressConfirmService;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/21
 * Time: 11:30
 */
class ExpressConfirmExportCollectQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "快递确认单汇总导出队列";
    }

    public function getDesc(): string
    {
        return "快递确认单汇总导出队列";
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
            $data=$this->params;
            $service = new ExpressConfirmService();
            $service->collectExport($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}