<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/13
 * Time: 17:21
 */
class InvoiceRule extends Validate
{
    protected $rule = [
        ['name', 'require|unique:InvoiceRule,name', '名称不能为空！|名称已存在！'],
        ['template_id', 'require', '模板为必选！']
    ];
}