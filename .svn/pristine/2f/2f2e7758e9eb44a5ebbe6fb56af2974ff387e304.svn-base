<?php
namespace app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\OrderSaleService;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 14:07
 */

class OrderSaleAdoptQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "退款审批通过队列";
    }

    public function getDesc(): string
    {
        return "退款审批通过队列";
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
            $id = $data['id'];
            $operator = $data['operator'];
            $remark = $data['remark'];
            $service = new OrderSaleService();
            $service->adopt($id, $operator, $remark);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:order_sale_adopt_queue', 'error_'.time(), $ex->getMessage());
        }
    }
}