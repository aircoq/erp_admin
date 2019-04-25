<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/04
 * Time: 11:54
 */
class WarehouseCargoClass extends  Validate
{
    protected $rule = [
        ['warehouse_id','require|number','仓库不能为空！|仓库为数字！'],
        ['code', 'require|unique:WarehouseCargoClass,code|max:20', '货位类型编码不能为空！|货位类型编码已经存在！|货位类型编编码最大长度为20！'],
        ['name', 'require|unique:WarehouseCargoClass,name^warehouse_id|max:20', '货位类型名称不能为空！|仓库货位类型名称已经存在！|货位类型编名称最大长度为20！'],
        ['width', 'require|number', '高度不能为空！|高度为数字！'],
        ['height', 'require|number', '高度不能为空！|高度为数字！'],
        ['length', 'require|number', '长度不能为空！|长度为数字！'],
        ['max_volume', 'require|number','货位最大体积不能为空|货位最大体积为数字'],
        ['rate', 'between:0,1', '体积率只能在[0,1]之间'],
        ['status', 'require','状态不能为空'],
    ];
    protected $scene = [
        'add'  =>  ['warehouse_id', 'code', 'name', 'width', 'height', 'length','rate', 'max_volume'],
        'edit'  =>  ['name','width', 'height', 'length','rate', 'max_volume'],
        'change_status' => ['status'],
        'lists' => ['warehouse_id'],
    ];
}
