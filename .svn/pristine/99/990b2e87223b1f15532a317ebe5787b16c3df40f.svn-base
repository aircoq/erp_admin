<?php

namespace app\index\queue;

use app\common\model\AccountUserMap;
use app\common\model\ChannelUserAccountMap;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\index\service\ManagerServer;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/23
 * Time: 12:03
 */
class AuthorizationQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "服务器成员信息回写";

    }

    public function getDesc(): string
    {
        return "服务器成员信息回写";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 20;
    }

    public function execute()
    {
        $info = $this->params;
        try {
            (new ManagerServer())->setAuthorization1($info['server_id'], [$info['user_id']]);
        } catch (Exception $e) {

        }
    }
}