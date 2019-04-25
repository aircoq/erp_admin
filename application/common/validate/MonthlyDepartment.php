<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class MonthlyDepartment extends Validate
{

    protected $rule = [
        ['name','require|unique:monthly_target_department,name^mode','部门名称不能为空！|部门名称已存在！'],
        ['leader_id','require','部门负责人不能为空！'],
    ];

}