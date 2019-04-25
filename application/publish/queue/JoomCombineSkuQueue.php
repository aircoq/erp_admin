<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-13
 * Time: 上午10:42
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;

class JoomCombineSkuQueue extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName():string
    {
        return 'joom捆绑sku队列';
    }
    public function getDesc():string
    {
        return 'joom捆绑sku队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {

    }
}