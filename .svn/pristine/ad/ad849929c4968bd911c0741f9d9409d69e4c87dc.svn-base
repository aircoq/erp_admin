<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Carrier extends Validate
{

    protected $rule = [
        ['code','require','物流代码不能为空！'],
        ['fullname','require|unique:Carrier,fullname','物流全称不能为空！|物流全称已存在！'],
        ['shortname','require|unique:Carrier,shortname','物流简称不能为空！|物流简称已存在！'],
        ['sequence_number','number|between:0,200|unique:Carrier,sequence_number','面单序列号为数字|面单序列号只能在0到100之间|面单序列号已存在！']
    ];

}