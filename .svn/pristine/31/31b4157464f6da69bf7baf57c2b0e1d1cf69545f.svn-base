<?php


namespace app\goods\queue;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsHelp;
use app\common\exception\QueueException;
use think\Exception;

class UpdatePurchaserIdBySupplierIdQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "根据供应商Id修改采购员队列";
    }

    public function getDesc(): string
    {
        return "根据供应商Id修改采购员队列";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        try {
            $good = new GoodsHelp();
            $data = $this->params;
            if(!isset($data['supplier_id'])||!$data['supplier_id']){
                throw new Exception('供应商id不能为空');
            }
            if(!isset($data['purchaser_id'])||!$data['purchaser_id']){
                throw new Exception('采购员id不能为空');
            }
            if(!isset($data['user_id'])){
                throw new Exception('用户id不能为空');
            }
            $good->updatePurchaserIdBySupplierId($data['supplier_id'],$data['purchaser_id'],$data['user_id']);
        }catch (Exception $ex){
            throw new QueueException($ex->getMessage());
        }
    }
}