<?php
namespace app\index\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/1
 * Time: 14:53
 */
class BuyerValidate extends Validate
{
    protected $rule = [
        ['channel_id', 'require|number','渠道ID为必须！| 渠道ID必须为数字！'],
        ['account_id', 'require|number','账号ID为必须！| 账号ID必须为数字！'],
        ['buyer_id', 'require','国家编码为必须！'],
    ];
}