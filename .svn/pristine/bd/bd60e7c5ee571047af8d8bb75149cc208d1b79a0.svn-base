<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class Unit extends  Validate
{
    protected $rule = [
        ['name','require|unique:Unit,name','单位名称不能为空！|单位名称已存在！'],
    ];
}