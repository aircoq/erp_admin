<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class PddAccount extends  Validate
{
    protected $rule = [
        ['code','require|unique:PddAccount,code','账号代码不能为空！|账号代码已存在！'],
        ['name','require|unique:PddAccount,name','账号名称不能为空！|账号名称已存在！'],
        ['name','require','pdd账号不能为空！'],
    ];
}