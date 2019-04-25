<?php
namespace app\index\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/1
 * Time: 14:53
 */
class BuyerAddressValidate extends Validate
{
    protected $rule = [
        ['channel_buyer_id', 'require|number','渠道买家关联ID为必须！| 渠道买家关联ID必须为数字！'],
        ['consignee', 'require','收货人为必须！'],
        ['country_code', 'require','国家编码为必须！'],
        ['address', 'require','详细地址为必须！'],
    ];
}