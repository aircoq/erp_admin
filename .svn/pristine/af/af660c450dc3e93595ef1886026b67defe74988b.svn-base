<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class Packing extends  Validate
{
    protected $rule = [
        ['title','require|unique:Packing,title','名称不能为空！|名称已存在！'],
        ['weight','number','重量必须为数字'],
        ['width','number','宽度必须为数字'],
        ['height','number','高度必须为数字'],
        ['depth','number','深度必须为数字'],
        ['supplier_id','number','供应商必须为数字'],
        ['currency_id','number','货币必须为数字'],
        ['cost_price','number','成本价必须为数字'],
    ];
}