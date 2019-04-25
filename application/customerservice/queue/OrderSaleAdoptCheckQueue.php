<?php

namespace app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\OrderSaleService;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2019/3/15
 * Time: 14:52
 */
class OrderSaleAdoptCheckQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return '售后单匹配自动审批规则队列';
    }

    public function getDesc(): string
    {
        return '售后单匹配自动审批规则队列';
    }

    public function getAuthor(): string
    {
        return 'hecheng';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $params = $this->params;
            $service = new OrderSaleService();
            $service->automaticAdoptCheck($params);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:order_sale_adopt_check_queue', 'error_'.time(), $ex->getMessage());
        }
    }


}