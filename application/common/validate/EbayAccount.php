<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class EbayAccount extends  Validate
{
    protected $rule = [       
        ['code','require|unique:EbayAccount,code','账号简称不能为空！|账号简称已存在！'],
        ['account_name','require|unique:EbayAccount,account_name','账号名称不能为空！|账号名称已存在！'],       
        //['email','require|unique:EbayAccount,email','email不能为空！|email已存在！'],
        
    ];
}