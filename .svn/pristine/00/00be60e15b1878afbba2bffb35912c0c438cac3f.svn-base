<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/10
 * Time: 10:24
 */
class PickingPackage extends  Validate
{
    protected $rule = [
        ['picking_id', 'require|number', '拣货单ID不能为空 | 拣货单ID必须为数字'],
        ['package_id', 'number', '包裹ID必须为数字'],
    ];
}