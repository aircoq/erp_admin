<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use app\index\service\WishShippingRateService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/18
 * Time: 10:03
 */
class WishShippingRateQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Wish重量与费用计算队列";

    }

    public function getDesc(): string
    {
        return "Wish重量与费用计算队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 10;
    }

    public function execute()
    {
        $info = $this->params;
        if (!$info) {
            return false;
        }
        try {
            (new WishShippingRateService())->shippingChargeRunOne($info);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}