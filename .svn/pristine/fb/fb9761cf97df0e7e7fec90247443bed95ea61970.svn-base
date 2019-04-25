<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 17:48
 */

namespace app\common\validate;

use think\Validate;

class WorldfirstValidate extends Validate
{

    protected $rule = [
        'server_id' => 'number',
        'wf_account' => 'require|max:50',
        'wf_password' => 'require|max:50',
        'operator_id' => 'require|number',
        'status' => 'in:0,1',
        'encrypted_answers' => 'max:100',
    ];

    protected $message = [
        'server_id' => '服务器必须为数值',
        'wf_account.require' => '登陆邮箱必须',
        'wf_account.max' => '登陆邮箱不能超过50个字符',
        'wf_password.require' => '登陆密码必须',
        'wf_password.max' => '登陆密码不能超过50个字符',
        'operator_id.require' => '操作人必须',
        'operator_id.number' => '操作人必须为数值',
        'status.in' => '系统状态取值错误',
    ];


    protected $scene = [
        'add'   =>  ['server_id','wf_account','wf_password','operator_id','status','encrypted_answers'],
        'edit'  =>  ['server_id','wf_account','operator_id','status','encrypted_answers'],
    ];
}