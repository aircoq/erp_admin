<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use think\Exception;
use app\goods\service\GoodsSku;

class GoodsSkuBatchStoppedQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "批量下架sku";
    }

    public function getDesc(): string
    {
        return "批量下架sku队列";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        try {
            $GoodsSku = new GoodsSku();
            $sku = $this->params['sku'];
            $user_id = $this->params['user_id'];
            $channelIds = $this->params['channel_ids'];
            $GoodsSku->stoppedSkuId($sku,$user_id,$channelIds);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}