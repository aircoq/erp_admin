<?php
namespace app\customerservice\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/1
 * Time: 14:53
 */
class OrderSaleValidate extends Validate
{
    protected $rule = [
        ['sale_number', 'require|unique:AfterSaleService,sale_number','售后订单编号不能为空！ | 售后订单编号已存在！'],
        ['order_id', 'require','订单ID不能为空！'],
        ['channel_id', 'require|number','渠道为必须！| 渠道必须为数字！'],
        ['account_id', 'require|number','账号为必须！| 账号必须为数字！'],
        ['type', 'require|number','售后单类型为必须！| 售后单类型必须为数字！'],
        ['order_number', 'require','订单编号为必须'],
        ['reason', 'require|number','售后原因为必须！| 售后原因必须为数字！'],
    ];
}