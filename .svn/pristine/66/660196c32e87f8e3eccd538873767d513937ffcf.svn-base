<?php

namespace app\index\queue;

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
class ServerUserReceive extends SwooleQueueJob
{
    public function getName(): string
    {
        return "服务器用户批量操作回调";

    }

    public function getDesc(): string
    {
        return "服务器用户批量操作回调";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 1;
    }

    public function execute()
    {
        $info = $this->params;
        try {
            (new ManagerServer())->serverReceive($info['server_id'], $info['userAd']);
            return true;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
}