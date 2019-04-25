<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/12/11
 * Time: 16:34
 */
class PackageReturn extends Validate
{
    protected $rule = [
        ['package_number', 'require|unique:PackageReturn,package_number', '订单包裹号不能为空！|该订单包裹已存在！'],
        ['warehouse_id', 'require|number', '仓库不能为空|仓库必须为数字'],
        ['channel_id', 'require|number', '渠道不能为空|渠道必须为数字'],
        ['channel_account_id', 'require|number', '账号不能为空|账号必须为数字'],
        ['order_number', 'require', '订单编号不能为空'],
        ['shipping_number', 'require', '运单号不能为空'],
        ['order_id', 'require|number', '订单ID不能为空|订单ID必须为数字'],
        ['shipping_id', 'require|number', '运输方式不能为空|运输方式ID必须为数字'],
    ];
}