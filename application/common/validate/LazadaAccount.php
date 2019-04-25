<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class LazadaAccount extends  Validate
{
    protected $rule = [
        ['code','require|unique:LazadaAccount,code','账号代码不能为空！|账号代码已存在！'],
        ['name','require|unique:LazadaAccount,name','账号名称不能为空！|账号名称已存在！'],
        ['lazada_name','require','lazada账号不能为空！'],
    ];
}