<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Task extends Validate
{

    protected $rule = [
        ['name','require|unique:Task,name','名称不能为空！|名称已存在！'],
        ['controller','require','执行控制器不能为空！'],
        ['args','require','参数不能为空！'],
    ];

}