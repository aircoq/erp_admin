<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/18
 * Time: 10:03
 */
class AccountUserMapUpdateQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "账号成员与服务器成员更新操作";

    }

    public function getDesc(): string
    {
        return "账号成员与服务器成员更新操作";
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
        if (!$info['accountId'] || !$info['user_id'] || !$info['server_id']) {
            return false;
        }
        try {
            (new AccountUserMapService())->updateAccountUserMap($info['accountId'], $info['user_id'], $info['server_id'], $info['is_add'],$info['user']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}