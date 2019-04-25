<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\OrderDetailService;

/**
 * 报表订单详情调整
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/12
 * Time: 17:23
 */
class OrderDetailExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "订单详情导出队列";
    }

    public function getDesc(): string
    {
        return "订单详情导出队列";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new OrderDetailService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}