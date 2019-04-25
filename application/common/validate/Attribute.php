<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class Attribute extends  Validate
{
    protected $rule = [
        ['name','require|unique:Attribute,name','属性名称不能为空！|属性名已存在！'],
        ['code','alphaNum', '属性代码为字母或者数字！']
    ];
}