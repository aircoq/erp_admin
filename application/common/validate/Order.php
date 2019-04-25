<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class Order extends Validate
{
    protected $rule = [
        ['order_number','require','订单编号不能为空！'],
        ['channel_id','require|integer','渠道不能为空！| 渠道值必须为数字！'],
        ['channel_account_id','require|integer','渠道账号不能为空！| 渠道账号值必须为数字！'],
        ['warehouse_id','require|integer','仓库不能为空！| 仓库值必须为数字！'],
        ['shipping_id','require|integer','运输方式不能为空！| 运输方式值必须为数字！'],
        ['order_amount','require|float|min:0','订单金额不能为空！| 订单金额必须为数字！| 订单金额必须大于0'],

    ];

    protected $scene = [
        'manual' => ['channel_id','channel_account_id','warehouse_id','shipping_id','order_amount']
    ];
}