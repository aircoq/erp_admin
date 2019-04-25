<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/13
 * Time: 9:57
 */
class VirtualRuleSet extends Validate
{
    protected $rule = [
        ['title', 'require|unique:VirtualRuleSet,title', '名称不能为空！|名称已存在！'],
    ];
}