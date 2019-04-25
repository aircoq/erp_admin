<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use app\index\service\ManagerServer;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/18
 * Time: 10:03
 */
class SendServerUpdateUserQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "与服务器通讯批量更新服务器用户信息";

    }

    public function getDesc(): string
    {
        return "与服务器通讯批量更新服务器用户信息";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 10;
    }

    public function execute()
    {
        $info = $this->params;
        if (!$info['server']) {
            return false;
        }
        try {
            (new ManagerServer())->sendServerUpdateUser($info['server'], $info['addUserData'], $info['deleteUser']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}