<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 17:41
 */

namespace app\common\validate;


use think\Validate;

class GoodsDiscount extends Validate
{
    protected $rule = [
        'sku_id' => 'require',
        'goods_id' => 'require',
        'inventory_price' => 'require',
        'last_purchase_price' => 'require',
        'new_price' => 'require',
        'warehouse_id' => 'require',
        'discount_type' => 'require|in:1,2',
        'discount_value' => 'require',
        'valid_time' => 'require',
        'over_time' => 'require',
        'status' => 'require|in:0,1,2,3,4',
        'remark' => 'max:254',
    ];

    protected $message = [
        'sku_id' => 'sku必须',
        'goods_id' => '商品ID必须',
        'inventory_price' => '库存成本价必须',
        'last_purchase_price' => '最后一次采购价必须',
        'new_price' => '最新报价必须',
        'warehouse_id' => '仓库必须',
        'discount_type.require' => '跌价类型必须',
        'discount_type.in' => '跌价类型不合法',
        'discount_value' => '跌价属性必须',
        'valid_time' => '开始时间必须',
        'over_time' => '结束时间必须',
        'status.require' => '状态必须',
        'status.in' => '状态不合法',
        'remark' => '备注不能超过255个字符',
    ];

    protected $scene = [
        'add' => ['sku_id', 'goods_id', 'inventory_price', 'last_purchase_price', 'new_price', 'warehouse_id', 'discount_type', 'discount_value', 'valid_time', 'over_time'],
        'edit' => ['sku_id', 'goods_id', 'inventory_price', 'last_purchase_price', 'new_price', 'warehouse_id', 'discount_type', 'discount_value', 'valid_time', 'over_time', 'status'],
        'status' => ['status'],
    ];
}