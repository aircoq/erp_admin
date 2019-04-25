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
class AccountUserMapBatchQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "账号成员批量操作";

    }

    public function getDesc(): string
    {
        return "账号成员批量操作";
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
            (new AccountUserMapService())->batchSetAccountUsers($info['channel_id'], $info['user_id'], $info['is_add'],$info['user']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}