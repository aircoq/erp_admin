<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Department extends Validate
{

    protected $rule = [
        ['name','require|unique:Department,name','部门名称不能为空！|部门名称已存在！'],
        ['leader_id','require','部门负责人不能为空！'],
    ];

}