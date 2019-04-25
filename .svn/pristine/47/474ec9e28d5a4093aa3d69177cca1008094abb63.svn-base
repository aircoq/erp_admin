<?php
namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/5/23
 * Time: 12:03
 */
class AccountUserMapNewQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "账号成员与服务器成员回写操作[新方式]";

    }

    public function getDesc(): string
    {
        return "账号成员与服务器成员回写操作[新方式]";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        $info = $this->params;
        try {
            (new AccountUserMapService())->writeBackNew($info);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}