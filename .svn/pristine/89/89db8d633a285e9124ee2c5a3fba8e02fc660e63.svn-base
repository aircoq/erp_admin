<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class FunmartAccount extends  Validate
{
    protected $rule = [
        ['account_name','require|unique:FummartAccount,account_name','账号名称不能为空！|账号名称已存在！'],
        ['code','require|unique:FummartAccount,code','账号简称不能为空！|账号简称已存在！'],
    ];

    protected $scene = [
        'add'  => ['account_name', 'code'],
        'edit' => ['code'],
    ];
}