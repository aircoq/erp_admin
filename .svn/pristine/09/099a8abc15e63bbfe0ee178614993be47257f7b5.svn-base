<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\controller\ServerSoftware;
use app\index\service\AccountUserMapService;
use app\index\service\SoftwareService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/11
 * Time: 9:15
 */
class ServerSoftwareBatchQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "服务器软件更新发起操作";

    }

    public function getDesc(): string
    {
        return "服务器软件更新发起操作";
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
            (new SoftwareService())->sendUpdate($info);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}