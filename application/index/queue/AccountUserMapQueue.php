<?php
namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/23
 * Time: 12:03
 */
class AccountUserMapQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "账号成员与服务器成员回写操作";

    }

    public function getDesc(): string
    {
        return "账号成员与服务器成员回写操作";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        $info = $this->params;
        try {
            $user = [
                'user_id' => 0,
                'realname' => '账号成员与服务器成员回写',
            ];
            (new AccountUserMapService())->writeBack($info,$user);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}