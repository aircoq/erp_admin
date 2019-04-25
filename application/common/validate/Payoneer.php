<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 18:09
 */

namespace app\common\validate;


use think\Validate;

class Payoneer extends Validate
{
    protected $rule = [
        'account_name' => 'require',
        'email_password' => 'require|max:100',
        'belong' => 'require|max:50',
        'phone' => 'require|max:20',
        'company_name' => 'require|max:100',
        'registered_name' => 'require|max:100',
        'client_code' => 'require|number',
        'status' => 'in:0,1',
        'operator_id' => 'require|number',
    ];

    protected $message = [
        'account_name' => '账户(邮箱)必须',
        'email_password.require'  => '邮箱密码必须',
        'email_password.max'  => '邮箱密码最多不能超过100个字符',
        'belong.require'  => '持有人必须',
        'belong.max'   => '持有人最多不能超过50个字符',
        'phone.require'  => '电话必须',
        'phone.max'  => '电话最多不能超过20个字符',
        'company_name.require'  => '公司名称必须',
        'company_name.max'  => '公司名称最多不能超过100个字符',
        'registered_name.require'  => '注册名称必须',
        'registered_name.max'  => '注册名称最多不能超过100个字符',
        'client_code.require'  => '客户ID必须',
        'client_code.number'  => '客户ID最多不能超过10个字符',
        'operator_id.require'  => '操作人必须',
        'operator_id.number'  => '操作人不合法',
        'status'  => '系统状态不合法',
    ];


    protected $scene = [
        'add'   =>  ['account_name','email_password','belong','phone','company_name','registered_name','client_code','operator_id'],
        'edit'  =>  ['account_name','belong','phone','company_name','registered_name','client_code','operator_id'],
    ];
}