<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Role extends Validate
{

    protected $rule = [
        ['name','require|unique:Role,name','角色名称不能为空！|角色名称已存在！'],
    ];

}