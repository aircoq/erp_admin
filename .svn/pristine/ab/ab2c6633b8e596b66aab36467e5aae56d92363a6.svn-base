<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\goods\service\GoodsSku;

class GoodsSkuAfterUpdateQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "商品sku修改后触发执行队列";
    }

    public function getDesc(): string
    {
        return "商品sku修改后触发执行队列";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        try {
            $old = $this->params['old'];
            $new = $this->params['new'];
            $GoodsSku = new GoodsSku();
            $GoodsSku->doAfterUpdate($old,$new);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}