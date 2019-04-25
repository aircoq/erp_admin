<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm.
 * User: laiyongfeng
 * Date: 2017/10/30
 * Time: 09:52
 */
class WarehouseAreaCategory extends  Validate
{
    protected $rule = [
        ['warehouse_id', 'require|number','仓库Id不能为空！|仓库Id为数字！'],
        ['warehouse_area_id', 'require|number','分区id不能为空！|分区Id为数字！'],
        ['category_id', 'require|number','分类Id不能为空！|分类Id为数字！'],
        ['category_ids', 'require','分类Id不能为空！|分类Id为数字！'],
    ];
    protected $scene = [
        'add'  => ['warehouse_id', 'warehouse_area_id', 'category_id'],
        'batch_add'  => ['warehouse_id', 'warehouse_area_id', 'category_ids'],
    ];
}