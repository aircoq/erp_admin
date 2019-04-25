<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/7/19
 * Time: 10:51
 */
class PriceRuleSet extends Validate
{
    protected $rule = [
        ['title','require|unique:PriceRuleSet,title','名称不能为空！| 规则名称已存在！'],
        ['operator_id','require','操作者id不能为空！'],
        ['operator','require','操作者名称不能为空！'],
        ['action_value','require','操作内容不能为空！']
    ];
}