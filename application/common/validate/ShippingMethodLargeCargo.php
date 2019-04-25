<?php
namespace app\common\validate;

use think\Validate;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-14
 * Time: 20:28
 */
class ShippingMethodLargeCargo extends Validate
{
    protected $rule = [
        ['shipping_id','require|unique:ShippingMethodLargeCargo','渠道id不能为空！|渠道已存在'],
    ];

}