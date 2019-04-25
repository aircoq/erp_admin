<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/22
 * Time: 10:07
 */

namespace app\common\validate;


use think\Validate;

class OberloAccount extends Validate
{
    protected $rule = [
        ['code','require|unique:OberloAccount,code','账号代码不能为空！|账号代码已存在！'],
        ['name','require','账号名称不能为空'],
        ['base_account_id','require|number|gt:0','账号基础资料ID不能为空|账号基础资料ID为数字|账号基础资料ID需大于0']
    ];
}