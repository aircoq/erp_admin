<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-12
 * Time: 下午2:47
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;

class JoomRsyncListing extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName():string
    {
        return 'joom同步线上listing队列';
    }
    public function getDesc():string
    {
        return 'joom同步线上listing队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {

    }
}