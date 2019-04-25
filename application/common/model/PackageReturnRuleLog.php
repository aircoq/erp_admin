<?php

namespace app\common\model;

use erp\ErpModel;
use think\Model;

class PackageReturnRuleLog extends ErpModel
{
    //操作类型 0-新增 1-修改 2-删除
    const TYPE_ADD  = 1;
    const TYPE_EDIT = 2;
    const TYPE_DLE  = 3;

    //
    protected function initialize()
    {
        parent::initialize();
    }
}
