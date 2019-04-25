<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/4/19
 * Time: 15:11
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;

class EbayAccountPerformanceSyncQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return "eBay账号表现数据同步";
    }

    public function getDesc(): string
    {
        return "eBay账号表现数据同步";
    }

    public function getAuthor(): string
    {
        return "wlw2533";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {

    }
}