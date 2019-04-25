<?php


namespace app\goods\queue;


use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsBrandsLink;

class GoodsSkuToBrandLinkQueue extends SwooleQueueJob
{
    public function getName() : string
    {
        return 'sku推送品连队列';
    }

    public function getDesc(): string
    {
        return 'sku推送品连队列';
    }

    public function getAuthor(): string
    {
        return 'zxh';
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $skuId = $this->params;
            $service = new GoodsBrandsLink();
            $service->skuPush($skuId);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}