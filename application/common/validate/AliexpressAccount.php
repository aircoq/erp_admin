<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class AliexpressAccount extends  Validate
{
    protected $rule = [
        ['code','require|unique:AliexpressAccount','账号代码不能为空！|账号代码已存在！'],
        ['account_name','require|unique:AliexpressAccount,account_name','账号名称不能为空！|账号名称已存在！'],
        ['trad_percent','require','交易费比例不能为空！']
    ];
}