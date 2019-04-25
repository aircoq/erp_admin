<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2018/12/10
 * Time: 20:22
 */
class WarehouseConfig extends Validate
{

    protected $rule = [
        ['warehouse_id','require','仓库id不能为空'],
        ['name', 'require|unique:WarehouseConfig,name^warehouse_id', '名称不能为空！|名称已存在！'],
        ['title', 'require|unique:WarehouseConfig,name^warehouse_id', '标题不能为空！|标题已存在！'],
        ['choose_type','require','参考类型不能为空！'],
        ['data_type','require','参数值类型不能为空！'],
        ['type','require','选择类型不能为空！'],
        ['config_id','require','站点配置id不能为空！'],
        ['test_print','require','是否测试打印不能为空！'],
        ['is_auto_check','require','是否字段复核不能为空！'],
        ['shelf_days','require','包裹预接收上架天数不能为空！'],
        ['is_divide_platform','require','是否支持平台分库存不能为空！'],
        ['weight_interval','require','集包称重区间不能为空！'],
    ];

    protected $scene = [
        'add'  =>  ['warehouse_id', 'name', 'title', 'choose_type', 'data_type', 'type'],
        'update'  =>  ['warehouse_id', 'config_id', 'name', 'title', 'choose_type', 'data_type', 'type'],
        'use'  =>  ['config_id'],
        'setting'  =>  ['warehouse_id', 'is_auto_check', 'test_print', 'shelf_days', 'is_divide_platform', 'weight_interval'],
        'system'  =>  ['warehouse_id']
    ];
}