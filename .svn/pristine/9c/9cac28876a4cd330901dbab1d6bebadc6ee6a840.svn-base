<?php
namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use app\index\service\ManagerServer;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/23
 * Time: 12:03
 */
class WriteBackServerAccount extends SwooleQueueJob
{
    public function getName(): string
    {
        return "回写服务器成员";

    }

    public function getDesc(): string
    {
        return "回写服务器成员";
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
//            (new ManagerServer())->setAuthorization($info['server_id'], $info['add'], $info['delete']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}