<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 17:12
 */

namespace app\common\validate;


use think\Validate;

class Lianlianpay extends Validate
{
    protected $rule = [
        'server_id' => 'number',
        'channel_id' => 'require|number',
        'account_id' => 'number',
        'site_code' => 'require',
        'lianlian_account' => 'require|max:50|number',
        'lianlian_name' => 'require|max:100',
        'operator_id' => 'require|number',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'server_id'     => '服务器不合法',
        'channel_id.require'  => '平台必须',
        'channel_id.number'   => '平台数据不合法',
        'account_id'  => '账号数据不合法',
        'site_code'  => '账号数据不合法',
        'lianlian_account.require'  => '收款账号必须',
        'lianlian_account.max'  => '收款账号最多不能超过50个字符',
        'lianlian_account.number'  => '收款账号必须为数值',
        'lianlian_name.require'  => '收款名称必须',
        'lianlian_name.max'  => '收款名称最多不能超过100个字符',
        'operator_id.require'  => '操作人必须',
        'operator_id.number'  => '操作人必须为数值',
        'status'  => '系统状态不合法',
    ];


    protected $scene = [
        'add'   =>  ['server_id','channel_id','account_id','site_code','lianlian_account','lianlian_name','operator_id','status'],
        'edit'  =>  ['server_id','channel_id','account_id','site_code','lianlian_account','lianlian_name','operator_id','status'],
    ];
}