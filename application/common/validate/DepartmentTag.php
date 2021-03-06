<?php
namespace app\common\validate;

use \think\Validate;


class DepartmentTag extends Validate
{

    protected $rule = [
        ['name','require','部门名称不能为空！'],
        ['code','require','标签编码不能为空！'],
    ];
}