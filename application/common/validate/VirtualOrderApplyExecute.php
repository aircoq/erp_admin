<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/10/9
 * Time: 15:21
 */

namespace app\common\validate;


use think\Validate;

class VirtualOrderApplyExecute extends Validate
{
    protected $rule = [
        ['execute_time', 'require|gt:0', '执行时间不能为空|执行时间必须大于0'],
        ['virtual_order_detail_apply_id', 'require', 'sku详情id不能为空'],
        ['transaction_order_number', 'require|unique:VirtualOrderApplyExecute', '交易订单号不能为空|交易订单号已存在'],
    ];

}