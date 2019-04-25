<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use app\index\service\BasicAccountService;
use app\order\service\OrderPackage;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/3/19
 * Time: 15:15
 */
class AccountChannelBaseIdQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "平台账号与账号基础资料关系回写";

    }

    public function getDesc(): string
    {
        return "平台账号与账号基础资料关系回写";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 20;
    }

    public function execute()
    {
        $info = $this->params;
        try {
            (new BasicAccountService())->updateBaseAccount($info);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}