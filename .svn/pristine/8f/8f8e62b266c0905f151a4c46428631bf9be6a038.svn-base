<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class CategoryQcItem extends  Validate
{
    protected $rule = [
        ['name','require|unique:CategoryQcItem,name','质检名称不能为空！|质检名称已存在！'],
    ];
}