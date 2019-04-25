<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/17
 * Time: 17:35
 */

namespace app\customerservice\queue;

use app\common\service\SwooleQueueJob;
use app\customerservice\service\ShopeeDisputeService;
use think\Exception;

class ShopeeReturnSyncQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return "同步Shopee退货信息";
    }

    public function getDesc(): string
    {
        return "同步Shopee退货信息";
    }

    public function getAuthor(): string
    {
        return "张伟";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 1;
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            (new ShopeeDisputeService())->syncReturn($this->params);

        }catch (\Exception $e){
            throw new Exception($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }





}