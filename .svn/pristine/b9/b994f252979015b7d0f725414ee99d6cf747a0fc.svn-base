<?php
namespace app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\OrderSaleExportService;

/**
 * Class OrderExportQueue
 * @package app
 */
class OrderSaleExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "售后处理导出队列";
    }

    public function getDesc(): string
    {
        return "售后处理导出队列";
    }

    public function getAuthor(): string
    {
        return "hecheng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new OrderSaleExportService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}