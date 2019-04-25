<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/28
 * Time: 20:06
 */
class OrderPackageHoldRecord extends Validate
{
    protected $rule = [
        ['order_number','require','订单编号不能为空！'],
        ['package_id','require|integer|unique:OrderPackageHoldRecord,package_id','包裹id不能为空！| 包裹id必须为数字！| 包裹不允许重复建拦截单'],
        ['reason','require','原因信息不能为空']
    ];
}