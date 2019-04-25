<?php


namespace app\finance\validate;


use think\Validate;

class BankAccount extends Validate
{
    public $rule = [
        'account_name' => 'require',
        'bank_code' => 'require',
        'bank_id' => 'require',
        'bank_key' => 'require',
        'province_id' => 'require',
        'city_id' => 'require',
        'deposit_bank' => 'require',
        'mobile' => 'require',
        'currency_code' => 'require',
        'money' => 'require',
        'initial_money' => 'require',
        'cashier_id' => 'require',
        'status' => 'require',
        'creator_id' => 'require',
        'create_time' => 'require',
        'updater_id' => 'require',
        'update_time' => 'require',
        'enable_time' => 'require',
        'forbidden_time' => 'require'
    ];

    public $message = [
        'account_name.require' => '账户名称不能为空',
        'bank_code.require' => '银行卡号',
        'bank_code.number' => '银行卡号只能为数字',
        'bank_id.require' => '银行id不能为空',
        'bank_key.require' => '联行号不能为空',
        'province_id.require' => '省份不能为空',
        'city_id.require' => '城市不能为空',
        'deposit_bank.require' => '开户行不能为空',
        'currency_code.require' => '币种不能为空',
        'mobile.require' => '预留手机号不能为空',
        'money.require' => '金额不能为空',
        'cashier_id.require' => '出纳员不能为空',
        'status.require' => '状态不能为空',
        'status.number' => '状态只能为数字',
        'create_time.require' => '创建时间不能为空',
        'creator_id.require' => '创建人不能为空',
        'updater_id.require' => '更新人不能为空',
        'update_time.require' => '更新时间不能为空',
        'bank_code.unique'=>'银行卡号已存在'
    ];

    public $scene = [
        'insert' => [
            'account_name' => 'require',
            'bank_id' => 'require',
            'bank_code' => 'require|number|unique:BankAccount,bank_code',
            'currency_code' => 'require',
            'deposit_bank' => 'require',
            'bank_key' => 'require',
            'cashier_id' => 'require',
            'money' => 'require',
            'creator_id' => 'require',
            'create_time' => 'require'
        ],
        'update' => [
            'bank_code' => 'number|unique:BankAccount,bank_code',
            'updater_id' => 'require',
            'update_time' => 'require'
        ]
    ];

}