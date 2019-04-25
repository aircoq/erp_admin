<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/23
 * Time: 13:54
 */
class Account extends Validate
{
    protected $rule = [

        ['channel_id', 'require', '渠道值不能为空!'],
//        ['phone_id', 'require', '手机号不能为空!'],
//        ['email_id', 'require', '注册邮箱不能为空!'],
        ['company_id', 'require', '公司名称不能为空!'],
        ['account_creator', 'require', '账号创建人不能为空!'],
        ['account_create_time', 'require', '账号创建时间不能为空!']
    ];
}