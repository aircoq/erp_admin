<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class User extends Validate
{

    protected $rule = [
        ['username', 'require|unique:User,username', '用户名不能为空！|用户名已存在！'],
        ['email', 'require|unique:User,email', '邮箱不能为空！|邮箱已存在！'],
        ['mobile', 'require|unique:User,mobile', '手机号不能为空！|手机号已存在！'],
        ['password', 'require', '密码不能为空！'],
        ['job', 'require', '职位不能为空！'],
        ['role_id', 'require', '角色ID不能为空！'],
    ];

}