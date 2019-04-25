<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/30
 * Time: 17:17
 */
class LocalBuyerAccount extends Validate
{
    protected $rule = [
        ['username', 'require|unique:LocalBuyerAccount,username^channel_id', '登录用户名不能为空！|该渠道登录用户名已存在！'],
        ['channel_id', 'require', '渠道必须指定！'],
        ['email', 'require', '邮箱地址不能为空！'],
        ['email_password', 'require', '邮箱密码不能为空！'],
        ['password', 'require', '登录密码不能为空！'],
        ['server_id', 'require', '服务器必须绑定！'],
        ['account_creator', 'require', '账号创建人不能为空！'],
        ['account_create_time', 'require', '账号创建时间不能为空！']
    ];
}