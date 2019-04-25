<?php


namespace app\index\validate;


use think\Validate;

class Email extends Validate
{
    protected $rule = [
        'email' => 'require',
        'phone_id' => 'require',
        'password' =>'require',
        'post_id'=>'require',
        'reg_id' => 'require',
        'reg_time' => 'require',
        'status' => 'require',
        'is_receive' => 'require',
        'is_send' => 'require',
        'create_time' => 'require',
        'creator_id' => 'require',
        'updater_id' => 'require',
        'update_time' => 'require',
        'channel' => 'require'
    ];

    protected $message = [
        'email.require' => '邮件号不能为空',
        'email.email' => '邮件号格式不正确',
        'email.unique' => '邮件号已存在',
        'password.require'=>'密码不能为空',
        'phone_id.require' => '手机号是必填的',
        'phone_id.number' => '手机Id须为整型',
        'reg_id.require' => '注册人不能为空',
        'reg_id.number' => '注册人Id须为整形',
        'reg_time.require' => '注册时间不能为空',
        'reg_time.number' => '注册时间须为整型',
        'status.require' => '状态是必填的',
        'status.number' => '状态为整型',
        'is_receive.require' => '接收邮件是必填的',
        'is_receive.number' => '接收邮件是整型',
        'is_send.require' => '发送邮件是必填的',
        'is_send.number' => '发送邮件是整型',
        'create_time.require' => '创建时间是必须的',
        'create_time.number' => '创建时间为整型',
        'creator_id.require' => '创建人必填',
        'creator_id.number' => '创建人须为整型',
        'updater_id.require' => '更新人必填',
        'updater_id.number' => '更新人Id须为整型',
        'update_time.require' => '更新时间必填',
        'update_time.number' => '更新时间须为整型',
        'channel.require' => '可用平台必填',
        'channel.number' => '可用平台值为整形',
        'post_id.require'=>'邮局id不能为空'
    ];

    protected $scene = [
        'insert' => [
            'email'=>'require|email|unique:Email,email',
            'password'=>'require',
            'phone_id'=>'require|number',
            'post_id'=>'require',
            'reg_id'=>'number',
            'reg_time'=>'number',
            'status'=>'require|number',
            'is_receive'=>'require|number',
            'is_send'=>'require|number',
            'create_time'=>'require|number',
            'creator_id'=>'require|number',
        ],
        'update' => [
            'email'=>'email|unique:Email,email',
            'phone_id'=>'number',
            'reg_id'=>'number',
            'reg_time'=>'number',
            'status'=>'number',
            'is_receive'=>'number',
            'is_send'=>'number',
            'update_time'=>'number',
            'updater_id'=>'number',
        ]

    ];

}