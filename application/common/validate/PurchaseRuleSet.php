<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class PurchaseRuleSet extends Validate
{
    protected $rule = [
        ['title', 'require|unique:PurchaseRuleSet,title', '名称不能为空！|名称已存在！'],
        ['rule_type', 'require|number|in:0,1,2', '采购阶段不能为空！|采购阶段格式错误!|采购阶段只能为:采购计划，采购单不等待剩余，采购单作废'],
    ];

    protected $scene = [
        'update' => ['rule_type'],
    ];
}