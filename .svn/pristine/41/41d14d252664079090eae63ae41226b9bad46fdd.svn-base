<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/21
 * Time: 14:46
 */
class AccountCompany extends Validate
{
    protected $rule = [
        ['company', 'require|unique:AccountCompany,company','公司名称不能为空！|公司名称已存在！'],
    ];

}