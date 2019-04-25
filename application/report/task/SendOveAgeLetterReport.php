<?php

namespace app\report\task;

use app\index\service\AbsTasker;
use app\report\service\OverAgeService;
use think\Exception;
use app\common\exception\TaskException;

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2019/04/01
 * Time: 08:36
 */
class SendOveAgeLetterReport extends AbsTasker
{
    public function getCreator()
    {
        return 'laiyongfeng';
    }

    public function getDesc()
    {
        return '发送操库龄提醒';
    }

    public function getName()
    {
        return '发送操库龄提醒';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $service = new OverAgeService();
            $service->sendLetter();
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}