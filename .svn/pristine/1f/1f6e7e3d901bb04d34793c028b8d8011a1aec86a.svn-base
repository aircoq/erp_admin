<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午1:37
 */

namespace app\index\queue;


use app\common\cache\Cache;
use app\common\exception\QueueAfterDoException;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use app\index\service\WishAccountHealthService;
use think\Exception;

class WishAccountHealthSendQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Wish健康监控数据-数据发送";
    }

    public function getDesc(): string
    {
        return "Wish健康监控数据发送";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        $id = $this->params;
        if (!is_numeric($id)) {
            throw new Exception('未知参数'. $id);
        }

        try {
            //发送数据；
            $serv = new WishAccountHealthService();
            $result = $serv->sendAccount2Spider($id);
            if (!$result) {
                throw new QueueAfterDoException('发送失败', 1000 * 60 * 60);
            }
        } catch (Exception $e) {
            throw new QueueAfterDoException($e->getMessage(). '|'. $e->getCode(). '|'. $e->getFile(), 1000 * 60 * 60);
        }
    }
}