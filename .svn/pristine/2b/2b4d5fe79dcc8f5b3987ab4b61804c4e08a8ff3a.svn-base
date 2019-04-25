<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\StatisticByGoods;


class StatisticByGoodsExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "SKU销量动态表导出队列";
    }

    public function getDesc(): string
    {
        return "SKU销量动态表导出队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 1;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new StatisticByGoods();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export:statistic_by_goods', 'error_'.time(), $ex->getMessage());
        }
    }
}