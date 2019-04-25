<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2017/04/08
 * Time: 17:53
 */
class MsgRuleSet extends Validate
{
    protected $rule = [
        ['title', 'require|unique:MsgRuleSet,title', '名称不能为空！|名称已存在！'],
        ['trigger_rule', 'require', '触发规则条件不能为空!'],
        ['send_email_rule', 'require|number', '发送邮箱规则不能为空!|发送邮箱规则为整形'],
    ];
}