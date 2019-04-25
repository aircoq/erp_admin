<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by tanbin.
 * Date: 2017/06/21
 * Time: 17:03
 */
class AllocationDetail extends  Validate
{
    protected $rule = [
        ['sku_id', 'require','sku_id不能为空!'],
        ['quantity', 'require|number|egt:1','商品数量不能为空！|商品数量为整形！|商品数量不能小于1'],
        ['goods_id', 'require','goods_id不能为空!'],
        ['goods_name', 'require','商品名称不能为空!'],
    ];
}

