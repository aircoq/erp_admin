<?php


namespace app\common\validate;


use think\Validate;

class GoodsSkuAlias extends Validate
{
    protected $rule = [
        ['sku_id', 'require', 'sku_id不能为空'],
        ['sku_code', 'require', 'sku不能为空'],
        ['alias', 'require|unique:GoodsSkuAlias,alias', '别名已存在'],
        ['type', 'require', '类型不能为空']
    ];

    protected $scene = [
        'insert' => ['sku_id', 'sku_code', 'alias','type'],
    ];
}