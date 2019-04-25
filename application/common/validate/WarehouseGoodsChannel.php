<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm.
 * User: laiyongfeng
 * Date: 2017/12/13
 * Time: 15:56
 */
class WarehouseGoodsChannel extends  Validate
{
    protected $rule = [
        ['sku_id', 'require|number','sku_id不能为空|sku_id为数字'],
        ['from_channel_id', 'require|number','调出平台不能为空|调出平台id为数字'],
        ['to_channel_id', 'require|number','借出平台不能为空|借出平台id为数字'],
        ['quantity', 'require|number','数量不能为空|数量为数字'],
        ['warehouse_id', 'require|number','仓库id不能为空|仓库id为数字'],
        ['apply_id', 'require|number','配货单id不能为空|备货单id为数字'],
        ['details', 'require','分配详情不能为空'],

    ];

    protected $scene = [
        'lend'  =>  ['sku_id', 'from_channel_id', 'to_channel_id', 'quantity', 'warehouse_id'],
        'allot'  =>  ['apply_id', 'details'],
    ];
}

