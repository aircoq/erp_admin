<?php

namespace app\common\validate;

use think\Validate;

/**
 * @desc 1688 验证类
 * @author Jimmy <554511322@qq.com>
 * @date 2018-01-19 15:18:11
 */
class Ali1688Account extends Validate
{

    //验证规则
    protected $rule = [
            ['code', 'require|unique:Ali1688Account', '编码不能为空！|简称(编码)重复!'],
            ['account_name', 'require|unique:Ali1688Account', '账号不能为空！|1688账号(账号)重复!'],
            ['membership', 'require|number|in:1,2,3,4', '会员身份不能为空！|会员身份只能为数字!|会员身份只能为:1: 企业单位, 2:事业单位或社会团体, 3: 个体经营，4:个人'],
            ['client_id', 'require|number', '应用key不能为空|应用key只能为数字!'],
            ['client_secret', 'require|length:1,50', '应用秘钥不能为空！|应用秘钥最低长度为1，最大为50'],
    ];
    //验证场景
    protected $scene = [
        'create' => ['code', 'account_name', 'membership']
    ];

}
