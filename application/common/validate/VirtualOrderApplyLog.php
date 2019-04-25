<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/10/11
 * Time: 9:28
 */

namespace app\common\validate;


use think\Validate;

class VirtualOrderApplyLog extends Validate
{

    protected $rule = [
        ['status', 'require|in:1,2,3,4,5,6,7', '状态不能为空|状态值不正确'],
        ['virtual_order_apply_id', 'require|number', '订单id不能为空|订单id为数字'],
        ['remark', 'require', '备注不能为空'],
        ['creator', 'require', '提交人不能为空'],
    ];
}