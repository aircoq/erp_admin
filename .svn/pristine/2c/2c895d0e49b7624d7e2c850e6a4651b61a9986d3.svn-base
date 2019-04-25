<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\AccountUserMapService;
use app\index\service\BasicAccountService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/19
 * Time: 15:15
 */
class ChannelAccountAddBatchQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "平台账号批量自动回写注册";

    }

    public function getDesc(): string
    {
        return "平台账号批量自动回写注册";
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
            (new BasicAccountService())->createChannelAccount($info);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}