<?php
namespace app\customerservice\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/1
 * Time: 14:53
 */
class SaleReasonValidate extends Validate
{
    protected $rule = [
        ['code', 'require','原因不能为空！'],
        ['remark', 'require|unique:AfterServiceReason,remark','原因说明不能为空！| 原因说明已存在！'],
        ['creator_id', 'require','创建者必须！'],
        ['sort', 'require|number','排序值必须！| 排序值必须为数字！'],
        ['create_time', 'require|number','创建时间必须！| 创建时间必须为数字！'],
    ];
}