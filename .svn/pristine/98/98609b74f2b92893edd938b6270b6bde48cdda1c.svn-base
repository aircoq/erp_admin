<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/8/22
 * Time: 15:15
 */
class AccountUserMapDelQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "移除某个用户下所有的资料账号成员";

    }

    public function getDesc(): string
    {
        return "移除某个用户下所有的资料账号成员";
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
            $user = [
                'user_id' => 0,
                'realname' => '用户离职，钉钉回写',
            ];
            (new AccountUserMapService())->delAccountUserMapByUserId($info,$user);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}