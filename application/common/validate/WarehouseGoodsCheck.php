<?php

namespace app\common\validate;

use think\Validate;

/**
 * @desc 盘点单 验证类
 * @author Jimmy <554511322@qq.com>
 * @date 2018-02-03 16:06:11
 */
class WarehouseGoodsCheck extends Validate
{

    //验证规则
    protected $rule = [
            ['warehouse_goods_check_id', 'require|unique:warehouse_goods_check_detail,warehouse_goods_check_id^sku_id^warehouse_cargo_id', '盘点单号必须存在|盘点单 SKU+货位数据重复!'],
    ];

}
