<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsTort;
use think\Exception;

class GoodsTortListingQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "侵权下架listing回写队列";
    }

    public function getDesc(): string
    {
        return "侵权下架listing回写队列";
    }

    public function getAuthor(): string
    {
        return "starzhan";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try{
            $GoodsTort = new GoodsTort();
            $GoodsTort->listingSave($this->params);
        }catch (\Exception $ex){
            throw $ex;
        }

    }
}