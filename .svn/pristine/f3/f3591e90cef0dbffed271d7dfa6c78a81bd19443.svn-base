<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class JoomAccount extends  Validate
{
    protected $rule = [
        ['code','require|unique:JoomAccount,code','账号代码不能为空！|账号代码已存在！'],
        ['account_name','require|unique:JoomAccount,account_name','账号名称不能为空！|账号名称已存在！'],
        ['merchant_id','require|unique:JoomAccount,merchant_id','商户ID不能为空！|商户ID已存在！']
    ];
}