<?php

namespace app\index\queue;

use app\common\service\SwooleQueueJob;
use app\index\service\DepartmentUserMapService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/3
 * Time: 17:15
 */
class DepartmentUserMapBatchQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "部门负责人更换,自动变更资料成员";

    }

    public function getDesc(): string
    {
        return "部门负责人更换,自动变更资料成员";
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
            $user = $info['user'] ?? [];
            (new DepartmentUserMapService())->setLeaderAccountUser($info['departmentId'], $info['leader'], $info['oldLeader'], $user);
        } catch (Exception $e) {

        }
    }
}