<?php

/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/30
 * Time: 11:16
 */

namespace app\goods\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsSku;
use think\Exception;

class GoodsSkuUpdateCostPriceQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return '更新sku成本价信息';
    }

    public function getDesc(): string
    {
        return '更新sku成本价信息';
    }

    public function getAuthor(): string
    {
        return 'starzhan';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }
    protected $maxFailPushCount = 0;

    public function execute()
    {
        $params = $this->params;
        try {
            $GoodsSku = new GoodsSku();
            $GoodsSku->updateCostPriceQueue($params['sku_id'], $params['cost_price'],$params['user_id']);
        } catch (Exception $exception) {
            throw new QueueException($exception->getMessage());
        }
    }
}