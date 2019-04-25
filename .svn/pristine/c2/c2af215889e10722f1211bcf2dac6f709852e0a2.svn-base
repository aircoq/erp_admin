<?php
namespace app\index\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/1
 * Time: 14:53
 */
class AccountUserValidate extends Validate
{
    protected $rule = [
        ['account_id', 'require|number','渠道账号为必须！| 渠道账号ID必须为数字！'],
        ['user_id', 'require|number','用户为必须！| 用户ID必须为数字！'],
    ];
}