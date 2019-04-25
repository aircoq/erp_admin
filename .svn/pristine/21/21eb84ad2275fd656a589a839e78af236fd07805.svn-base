<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsBrandsLink;
use app\goods\service\GoodsToDistribution;
use app\common\cache\Cache;

class GoodsToDistributionQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "产品推送分销队列";
    }

    public function getDesc(): string
    {
        return "产品推送分销队列";
    }

    public function getAuthor(): string
    {
        return "starzhan";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new GoodsBrandsLink();
            $service->createGoods($data);
        }catch (\Exception $ex){
            throw $ex;
        }
    }
}