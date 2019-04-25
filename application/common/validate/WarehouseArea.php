<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/03
 * Time: 14:14
 */
class WarehouseArea extends  Validate
{
    protected $rule = [
        ['warehouse_id','require|number','仓库Id不能为空！|仓库Id为数字！'],
        ['code', 'require|unique:WarehouseArea,code^warehouse_id', '仓库分区编码不能为空！|仓库分区编码已经存在！'],
        ['name', 'require|unique:WarehouseArea,name^warehouse_id|max:40', '仓库分区名称不能为空！|仓库分区名称已经存在！|仓库分区名称长度不能超过40！'],
        ['status', 'require','状态不能为空'],
        ['floor_id', 'number','楼层为数字！'],
    ];

    protected $scene = [
        'add'  => ['warehouse_id', 'code', 'name','floor_id'],
        'change_status' => ['status'],
        'edit' => ['name', 'floor_id'],
        'lists' => ['warehouse_id'],
    ];
}