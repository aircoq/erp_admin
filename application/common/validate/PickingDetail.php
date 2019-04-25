<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/10
 * Time: 10:24
 */
class PickingDetail extends Validate
{
    protected $rule = [
        ['picking_id', 'require|number', '拣货单ID不能为空 | 拣货单ID必须为数字'],
        ['sku_id', 'number', 'SkuID必须为数字'],
        ['warehouse_cargo_id', 'number', '货位必须为数字'],
        ['quantity', 'number', '数量必须为数字'],
        ['picking_quantity', 'number', '拣货数量必须为数字'],
    ];
}