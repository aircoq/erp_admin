<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class QcItem extends  Validate
{
    protected $rule = [
        ['name','require|unique:QcItem,name','质检名称不能为空！|质检名称已存在！'],
    ];
}