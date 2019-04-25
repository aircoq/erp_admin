<?php
namespace app\index\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/1
 * Time: 14:53
 */
class AccountApplyValidate extends Validate
{
    protected $rule = [
        'channel_id'=>'require',
        'company_id'=>'require',
        'server_id'=>'require',
        'phone_id'=>'require'
    ];

    protected $message = [
        'channel_id.require'=>'平台必填',
        'company_id.require'=>'公司名称必填',
        'server_id.require'=>'服务器必填',
        'phone_id.require'=>'手机必填',
        'is_kyc.require'=>'是否触发kyc必填',
        'reg_result.require'=>'注册结果必填',
    ];
    protected $scene = [
        'save_base'=>[
            'channel_id'=>'require',
            'company_id'=>'require',
           // 'server_id'=>'require',
            'phone_id'=>'require'
        ],
        'save_audit_2'=>[
            'is_kyc'=>'require',
        ],
        'save_result'=>[
            'reg_result'=>'require'
        ]
    ];
}