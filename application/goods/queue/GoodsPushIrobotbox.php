<?php

/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/30
 * Time: 11:16
 */

namespace app\goods\queue;

use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsToIrobotbox;
use think\Exception;

class GoodsPushIrobotbox extends SwooleQueueJob
{

    public function getName(): string
    {
        return '商品推送赛盒';
    }

    public function getDesc(): string
    {
        return '商品推送赛盒';
    }

    public function getAuthor(): string
    {
        return '詹老师';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }
    protected $maxFailPushCount = 0;

    public function execute()
    {
        $params = $this->params;
        try {
            $GoodsToIrobotbox = new GoodsToIrobotbox();
            $GoodsToIrobotbox->upload($params);
        } catch (Exception $exception) {
            throw new QueueException($exception->getMessage());
        }
    }
}