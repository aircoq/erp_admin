<?php
namespace app\customerservice\queue;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\customerservice\service\OrderSaleService;
use app\order\service\ManualOrderService;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/10/24
 * Time: 14:37
 */
class OrderSaleAddQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "新增售后单队列";
    }

    public function getDesc(): string
    {
        return "新增售后单队列";
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
            $service = new OrderSaleService();
            $result = $service->add($data);
            if (!empty($result)) {
                $manualOrderService = new ManualOrderService();
                $manualOrderService->delCache($data['package_id']); //如果生成售后单成功，删除缓存。
                $arr = [];
                $operator = [];
                $operator['id'] = $data['operator_id'];
                $operator['operator'] = $data['operator'];
                $arr['id'] = $result['id'];
                $arr['operator'] = $operator;
                (new UniqueQueuer(OrderSaleAdoptQueue::class))->push($arr);
            }
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:order_sale_add_queue', 'error_'.time(), $ex->getMessage());
        }
    }
}