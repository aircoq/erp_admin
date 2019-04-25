<?php

namespace app\index\queue;

use app\common\exception\QueueAfterDoException;
use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use app\index\service\ManagerServer;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/2/25
 * Time: 15:15
 */
class ServerUserSendQueue extends SwooleQueueJob
{


    protected static $maxRunnerCount = 1000;

    protected $maxFailPushCount = 2;

    public function getName(): string
    {
        return "服务器用户批量操作发起";

    }

    public function getDesc(): string
    {
        return "服务器用户批量操作发起";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 20;
    }

    public function execute()
    {
        $info = $this->params;
        try {
            (new ManagerServer())->sendServer($info['server'], $info['userAd']);
        } catch (\Exception $e) {
//            throw new QueueAfterDoException('发送失败:'.$e->getMessage(), 600);
            throw new Exception($e->getMessage() . $e->getLine());
        }
    }
}