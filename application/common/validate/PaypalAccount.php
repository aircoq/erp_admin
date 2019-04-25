<?php

namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: Zhuda
 * Date: 2019/04/01
 * Time: 9:57
 */
class PaypalAccount extends Validate
{

    protected $rule = [
        'server_id' => 'require|number',
        'account_name' => 'require',
        'email_password' => 'require',
        'api_user_name' => 'require',
        'api_secret' => 'require',
        'api_signature' => 'require',
        'ip_address' => 'require',
        'belong' => 'require',
        'phone' => 'require',
        'type' => 'require|in:1,2',
        'credit_card' => 'require',
        'operator_id' => 'require',
        'withdrawals_type' => 'require',
    ];

    protected $message = [
        'server_id.require' => '请选择服务器',
        'server_id.number' => '请选择服务器',
        'account_name' => '账号名称必须',
        'email_password' => '密码必须',
        'api_user_name' => 'API用户名必须',
        'api_secret' => 'API密码必须',
        'api_signature' => 'API签名必须',
        'ip_address' => '服务器地址必须',
        'belong' => '账户持有人必须',
        'phone' => '电话必须',
        'type.require' => '账号收款类型必须',
        'type.in' => '账号收款类型不合法',
        'credit_card' => '绑定信用卡必须',
        'operator_id' => '操作人必须',
        'withdrawals_type' => '提款类型必须',
    ];


    protected $scene = [
        'add' => ['server_id', 'account_name', 'email_password', 'belong', 'phone', 'type', 'credit_card', 'operator_id', 'withdrawals_type'],
        'edit' => ['server_id', 'belong', 'phone', 'type', 'credit_card', 'operator_id', 'withdrawals_type'],
    ];


}