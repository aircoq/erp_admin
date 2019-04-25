<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class Brand extends  Validate
{
    protected $rule = [
        ['name','require|unique:Brand,name','品牌名称不能为空！|品牌名称已存在！'],
    ];
}