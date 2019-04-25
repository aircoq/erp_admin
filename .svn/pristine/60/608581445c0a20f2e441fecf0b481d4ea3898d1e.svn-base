<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstrom.
 * User: xueli
 * Date:2018/12/12
 * Time: 9；30
 */
class WarehouseCargoShift extends  Validate
{
    protected $rule = [
        ['warehouse_id','require|number','仓库Id不能为空！|仓库Id为数字！'],

        ['warehouse_cargo_id','require|number','货位Id不能为空！|货位Id为数字！'],
        ['goods_id', 'require|number', '商品id不能为空！| 商品id为数字！'],
        ['sku_id', 'require|number|unique:WarehouseCargoShift,warehouse_cargo_id^sku_id', 'sku_id不能为空！| sku_id为数字！'],
        ['sku', 'require', 'sku不能为空！'],
        ['quantity', 'require|number', '数量不能为空！| 数量为数字！'],
        ['warehouse_area_type', 'require|number', '分区功能类型不能为空！| 分区功能类型为数字！'],
        ['old_cargo_id', 'require|number', '原货位不能为空！| 原货位id为数字！'],
        ['new_cargo_id', 'require|number', '新货位不能为空！| 新货位id为数字！'],
        ['warehouse_cargo_code', 'require', '货位号不能为空!'],
        ['hold_quantity', 'require|number', '冻结库存不能为空!'],
        ['id', 'require|number', '库存记录ID不能为空!'],

    ];

    protected $scene = [
        'shift'  => ['old_cargo_id' ,'new_cargo_id' ,'sku_id'=>'require|number', 'quantity'],


    ];
}