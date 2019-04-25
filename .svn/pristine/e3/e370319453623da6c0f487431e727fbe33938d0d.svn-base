<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/04
 * Time: 16:44
 */
class WarehouseShelf extends  Validate
{
    protected $rule = [
        ['warehouse_id', 'require|number','仓库不能为空！|仓库为整形！'],
        ['warehouse_area_id', 'require|number', '仓库分区不能为空！|仓库分区为整形'],
        ['code', 'require|unique:WarehouseShelf,code^warehouse_area_id|max:20', '货架号不能为空！|货架号已经存在！|货架号最大长度为20！'],
        ['pass_num', 'require|/^\d{2}$/','货架通道不能为空！|货架通道固定为两位数！'],
        ['seat_num', 'require|/^[1-9]/','货架位置不能为空！|货架货架位置格式有误！'],
        ['column', 'require|max:20','货架列数不能为空！|货架列数为小于35的整数！'],
        ['row', 'require|max:20','货架行数不能为空！|货架行数为小于35的整数！'],
        ['start_pass_num', 'require|/^\d{2}$/','通道开始位置不能为空不能为空！|通道结束位置固定为两位数！'],
        ['end_pass_num', 'require|/^\d{2}$/','通道结束位置不能为空不能为空!|通道结束位置固定为两位数！'],
        ['start_seat_num', 'require','每个通道货架开始位置不能为空!'],
        ['end_seat_num', 'require','每个通道货架结束位置不能为空!'],
        ['status', 'require','状态不能为空!'],
        ['sku_num', 'require|number','sku品种数不能为空!|sku品种数整形！'],
        ['cargo_class_id', 'require|number','货位类型不能为空！|货位类型id为整形！'],
        ['face_aisle', '/^\d{2}$/','对面通道固定为两位数！'],
    ];

    protected $scene = [
        'add_one'  => ['warehouse_id', 'warehouse_area_id', 'row', 'pass_num', 'seat_num', 'face_aisle'], //添加单个货架
        'add_one_auto'  => ['warehouse_id', 'warehouse_area_id', 'row', 'pass_num', 'seat_num', 'column','sku_num', 'cargo_class_id', 'face_aisle'], //添加单个货架自动生成货位号
        'add_mult'  => ['warehouse_id', 'warehouse_area_id', 'row', 'pass_num', 'start_seat_num', 'end_seat_num', 'face_aisle'], //添加多个货架
        'add_mult_auto'  => ['warehouse_id', 'warehouse_area_id', 'row', 'pass_num' , 'start_seat_num', 'end_seat_num', 'column', 'row', 'sku_num', 'cargo_class_id', 'face_aisle'], //添加多个货架自动生成货位号
        'check_code' => ['code'],
        'add'  => ['row', 'warehouse_id', 'warehouse_area_id', 'code', 'face_aisle'],
        'edit' => ['code'],
        'change_status' => ['status'],
        'face_aisle' => ['warehouse_area_id', 'pass_num'],
        'lists' => ['warehosue_area_id'],
    ];
}

