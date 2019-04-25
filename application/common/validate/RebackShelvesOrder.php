<?php

/**
 * @desc 退货上架单列表验证器
 * @author Jimmy
 * @date 2017-12-06 14:49:11
 */

namespace app\common\validate;

use think\Validate;

class RebackShelvesOrder extends Validate
{

    //验证规则
    protected $rule = [
            ['warehouse_id', 'require|number', '仓库编码不能为空！|仓库编码只能为数字!'],
            ['warehouse_area_type', 'require|number|in:11,12,13,14,15,21,22', '仓库分区不能为空！|仓库分区只能为数字!'],
            ['sku', 'require|length:1,15', 'sku不能为空|sku长度限制为1-20个字符！'],
            ['sku_id', 'require|number', 'sku id不能为空！|sku id 只能为数字！'],
            ['warehouse_cargo_id', 'require|number', '仓库货位ID不能为空！|仓库货位ID只能为数字！'],
            ['warehouse_cargo_code', 'require|length:1,10', '仓库货位编码不能为空！|仓库货位编码长度限制为1-10个字符！'],
            ['quantity', 'require|number', '需要重返上架的数量不能为空！|需要重返上架的数量只能为数字！'],
            ['wait_upload_quantity', 'require|number', '待重返上架的数量不能为空！|待重返上架的数量只能为数字！'],
            ['creator_id', 'require|number', '创建者不能为空！|创建者只能为数字！'],
    ];
    //验证场景
    protected $scene = [
        'create' => ['warehouse_id','sku','quantity']
    ];

}
