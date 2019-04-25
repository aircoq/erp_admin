<?php
namespace app\index\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/8
 * Time: 14:53
 */
class Software extends Validate
{
    protected $rule = [
        ['version', 'require','客户端版本号为必须！'],
        ['software_type', 'require|number','类型为必须！| 类型必须为数字！'],
        ['remark', 'require','备注为必须！'],
    ];


}