<?php

namespace app\index\task;

use app\index\service\AbsTasker;

class ExportMemberShipMap extends AbsTasker
{
    public function getName()
    {
        return "导入账号关联信息";
    }

    public function getDesc()
    {
        return "导入账号关联信息";
    }

    public function getCreator()
    {
        return "Phill";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $re = (new \app\index\service\MemberShipService())->saveAllDir();
    }
}
