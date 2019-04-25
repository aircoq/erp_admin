<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/10/05
 * Time: 14:13
 */
class VirtualOrder extends Validate
{
    protected $rule = [
        ['channel_id', 'require', '平台id不能为空'],
//        ['account_id', 'require', '账号id不能为空'],
        ['order_number', 'require|unique:VirtualOrder,channel_id^order_number', '虚拟订单号不能为空|虚拟订单号已存在']
    ];

    protected $scene = [
        'edit' => ['channel_id','order_number']
    ];
}