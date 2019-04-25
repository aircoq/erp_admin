<?php


namespace app\common\validate;


use think\Validate;

class PackingConsumeLog extends Validate
{

    protected $rule = [
        ['warehouse_id','require','仓库不能为空'],
        ['package_id','require|number','包裹id不能为空|包裹id必须为数字'],
        ['package_type','require','包裹类型不能为空'],
        ['sku_quantity','require','数量不能为空'],
        ['creator_id','require','创建人不能为空'],
        ['create_time','require','创建时间不能为空'],
        ['end_time','require','结束时间不能为空']
    ];
}