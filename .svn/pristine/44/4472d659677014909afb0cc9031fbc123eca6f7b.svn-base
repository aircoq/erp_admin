<?php


namespace app\common\validate;


use think\Validate;

class ReadyInventoryApplyList extends Validate
{
    protected $rule = [
        ['warehouse_id','require|integer','仓库不能为空！| 仓库id必须为数字'],
        ['sku_id','require|integer','sku_id未能为空！| sku_id必须为整形'],
        ['sku','require','sku不能为空！'],
        ['channel_id','require','平台不能为空！'],
        ['quantity','require|integer','申请数量不能为空！| 申请数量必须为整数'],
    ];
}