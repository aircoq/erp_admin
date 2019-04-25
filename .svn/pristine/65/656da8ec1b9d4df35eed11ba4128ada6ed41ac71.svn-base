<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 17:18
 */

namespace app\common\validate;

use \think\Validate;

class CreditCard extends Validate
{
    protected $rule = [
        'id'          =>  'require|number',
        'card_number' =>  'require|max:25|number',
        'card_master' =>  'require|max:32',
        'card_status' =>  'require|in:1,2,3',
        'validity_date' =>  'require|max:10',
        'security_code' =>  'require|max:20',
        'card_category' =>  'require|max:32',
        'bank_id' =>  'require|number',
        'synchronize_status' =>  'require|number',
    ];

    protected $message = [
        'card_number.require' => '卡号必须',
        'card_number.max'     => '卡号最多不能超过25个字符',
        'card_number.number'  => '卡号必须为数字',
        'card_master'  => '卡主姓名必须',
        'card_status'   => '信用卡状态必须在（1,2,3）之间',
        'validity_date.require'  => '信用卡有效期必须',
        'validity_date.max'  => '信用卡有效期过长',
        'security_code.require'  => '信用卡安全码必须',
        'security_code.max'  => '信用卡安全码不能超过20个字符',
        'card_category.require'  => '信用卡类别必须',
        'card_category.max'  => '信用卡类别最多不能超过32个字符',
        'bank_id.require'  => '关联银行必须',
        'bank_id.number' => '关联银行ID必须为数值',
        'synchronize_status' => '数据同步状态必须为数值',
        ];


    protected $scene = [
        'add'   =>  ['card_number','card_master','card_status','validity_date','security_code','card_category','bank_id','synchronize_status'],
        'edit'  =>  ['id','card_number','card_master','card_status','validity_date','security_code','card_category','bank_id','synchronize_status'],
    ];

}