<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstrom.
 * User: laiyongfeng
 * Date: 2017/11/21
 * Time: 11:17
 */
class WarehouseCargoGoods extends  Validate
{
    protected $rule = [
        ['warehouse_id','require|number','仓库Id不能为空！|仓库Id为数字！'],
        ['warehouse_area_id','require|number','仓库分区Id不能为空！|仓库分区Id为数字！'],
        ['warehouse_cargo_id','require|number','货位Id不能为空！|货位Id为数字！'],
        ['goods_id', 'require|number', '商品id不能为空！| 商品id为数字！'],
        ['sku_id', 'require|number|unique:WarehouseCargoGoods,warehouse_cargo_id^sku_id', 'sku_id不能为空！| sku_id为数字！|仓库分区编码已经存在！'],
        ['sku', 'require', 'sku不能为空！'],
        ['quantity', 'require|number', '数量不能为空！| 数量为数字！'],
        ['warehouse_area_type', 'require|number', '分区功能类型不能为空！| 分区功能类型为数字！'],
        ['from_cargo_id', 'require|number', '原货位不能为空！| 原货位id为数字！'],
        ['to_cargo_id', 'require|number', '新货位不能为空！| 新货位id为数字！'],
        ['type', 'require', '类型不能为空!'],
        ['warehouse_cargo_code', 'require', '货位号不能为空!'],
        ['hold_quantity', 'require|number', '冻结库存不能为空!'],
        ['id', 'require|number', '库存记录ID不能为空!'],
        ['shift_info', 'require', '转移信息不能为空!'],
    ];

    protected $scene = [
        'add'  => ['warehouse_id', 'warehouse_area_id', 'warehouse_cargo_id', 'goods_id', 'sku_id' , 'sku', 'quantity','app_type'],
        'logs'  => ['warehouse_cargo_id' ,'sku_id'=>'number'],
        'shift'  => ['from_cargo_id' ,'to_cargo_id' ,'sku_id'=>'require|number', 'quantity'],
        'pad_shift'  => ['warehouse_id'],
        'bind'  => ['warehouse_id', 'sku', 'warehouse_cargo_code', 'warehouse_area_type'],
        'auto_bind'  => ['warehouse_id', 'sku', 'warehouse_area_type'],
        'hold_modify'  => ['id', 'hold_quantity'],
        'batch_shift'  => ['shift_info']
    ];
}