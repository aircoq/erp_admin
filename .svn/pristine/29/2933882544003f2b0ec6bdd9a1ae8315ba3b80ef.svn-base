<?php


namespace app\goods\queue;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsHelp;
use app\common\exception\QueueException;
use think\Exception;

class updatePurchaserIdQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "修改采购员队列";
    }

    public function getDesc(): string
    {
        return "修改采购员队列";
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
            if(!isset($data['old_purchaser_id'])||!$data['old_purchaser_id']){
                throw new Exception('旧采购员不能为空');
            }
            if(!isset($data['new_purchaser_id'])||!$data['new_purchaser_id']){
                throw new Exception('新采购员id不能为空');
            }
            if(!isset($data['user_id'])){
                throw new Exception('用户id不能为空');
            }
            $good->updatePurchaserIdByPurchaserId($data['old_purchaser_id'],$data['new_purchaser_id'],$data['user_id']);
        }catch (Exception $ex){
            throw new QueueException($ex->getMessage());
        }
    }
}