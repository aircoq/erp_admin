<?php

namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class GoodsSku extends Validate
{
    protected $rule = [
        ['spu_name', 'require', '产品中文名称不能为空'],
        ['sku', 'require|unique:GoodsSku,sku', 'sku不能为空|sku已存在'],
        ['cost_price', 'require', '采购价格不能为空'],
        ['weight', 'require', '产品重量(g)不能为空'],
        ['status', 'require', '出售状态不能为空'],
        ['goods_id', 'require', 'goodid不能为空'],
        ['sku_attributes', 'require', 'sku属性不能为空！'],
        ['cost_price', 'require', '成本价不能为空！'],
        ['retail_price', 'require', '零售价不能为空！'],
        ['length', 'require', '长度不能为空！'],
        ['width', 'require', '宽度不能为空！'],
        ['height', 'require', '高度不能为空！'],
    ];

    protected $scene = [
        'insert' => ['spu_name', 'spu', 'cost_price'],
        'import' => ['spu_name', 'cost_price', 'weight', 'status'],
        'preDev' => ['sku', 'name', 'retail_price', 'cost_price', 'weight', 'sku_attributes'],
        'add'=>['sku','goods_id','sku_attributes','spu_name','cost_price','retail_price','weight','length','width','height'],
        'dev'=>['goods_id','sku_attributes','spu_name','cost_price','retail_price','weight','length','width','height']
    ];
}