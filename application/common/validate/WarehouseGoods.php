<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm.
 * User: laiyongfeng
 * Date: 2017/12/13
 * Time: 10:46
 */
class WarehouseGoods extends  Validate
{
    protected $rule = [
        ['sku_id', 'require|number','sku_id不能为空|sku_id为数字'],
        ['sku_ids', 'require','货品id不能为空'],
        ['channel_id', 'require|number','平台id不能为空|平台id为数字'],
        ['quantity', 'require|number','备货数量不能为空|备货数量为数字'],
        ['warehouse_id', 'require|number','仓库不能为空|仓库为数字'],
        ['details', 'require','sku列表信息不能为空！'],
        ['extension', 'require','扩展名不能为空！'],
        ['content', 'require','导入内容不能为空！'],
        ['sku_id', 'require','sku_id不能为空！'],
        ['transit_warehouse_id', 'require','中转仓ID不能为空！'],

    ];

    protected $scene = [
        'apply'  =>  ['details', 'channel_id', 'warehouse_id', 'transit_warehouse_id'],
        'apply_info'  =>  ['sku_ids'],
        'logs'  =>  ['sku_id', 'warehouse_id'],
        'lists'  =>  ['warehouse_id'],
        'purchase_in'  =>  ['warehouse_id'],
        'init'  =>  ['extension', 'content'],
        'channel_detail'  =>  ['warehouse_id', 'sku_id']
    ];
}

