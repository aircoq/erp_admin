<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/06
 * Time: 17:03
 */
class WarehouseCargo extends  Validate
{
    protected $rule = [
        ['warehouse_id', 'require|number','仓库不能为空！|仓库为整形！'],
        ['warehouse_area_id', 'require|number', '仓库分区不能为空！|仓库分区为整形'],
        ['warehouse_shelf_id', 'require|number', '仓库货架不能为空!|仓库货架为整形'],
        ['code', 'require|unique:WarehouseCargo,code^warehouse_id|max:20', '货位号不能为空！|货位号已经存在！|货位号最大长度为20！'],
        ['status', 'require|number|between:0,1', '状态不能为空！|状态必须必须是数字！|状态只能取0或1！'],
        ['ids', 'require', '货位ID不能为空！'],
        ['cargo_class_id', 'require|number','货位类型不能为空！|货位类型id为整形！'],
        ['cargo_num', 'require|/^([A-Za-z1-9]){2}$/','货位号不能为空！|货位号只能为两位字符！'],
        ['row_start', 'require|/^[1-9]\d*$/','货位行开始不能为空！|货位行开始为大于0的整数！'],
        ['row_end', 'require|/^[1-9]\d*$/','货位行结束不能为空！|货位行结束为大于0的整数！'],
        ['column_start', 'require|/^[1-9]\d*$/','货位列开始不能为空！|货位行开始为大于0的整数！'],
        ['column_end', 'require|/^[1-9]\d*$/','货位列结束不能为空！|货位列结束为大于0的整数！'],
        ['sku_num', 'require|number','sku品种数不能为空！|sku品种数为整形！'],
        ['sku', 'require', 'sku不能为空'],
        ['warehouse_area_type', 'require|number', '分区功能不能为空！| 分区功能为整数！'],

    ];

    protected $scene = [
        'add'  =>  ['code', 'warehouse_id', 'warehouse_area_id', 'warehouse_shelf_id','cargo_class_id', 'sku_num'],
        'change_status'  =>  ['status'],
        'batch_change_status'  =>  ['ids', 'status'],
        'batch_del'  =>  ['ids'],
        'add_one'  =>  ['warehouse_id', 'warehouse_area_id', 'warehouse_shelf_id', 'cargo_class_id', 'cargo_num', 'sku_num'],
        'add_mult'  =>  ['warehouse_id', 'warehouse_area_id', 'warehouse_shelf_id', 'cargo_class_id','row_start','row_end','column_start','column_end', 'sku_num'],
        'check_code'  =>  ['code'],
        'list'  =>  ['warehouse_id'],
        'print'  =>  ['ids'],
        'update'  =>  ['cargo_class_id', 'sku_num'],
        'recommend'  =>  ['warehouse_id', 'sku', 'warehouse_area_type'],
    ];
}

