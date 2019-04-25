<?php


namespace app\common\validate;

use \think\Validate;

class GoodsDev extends Validate
{
    protected $rule = [

    ];
    protected $message = [
        'goods_id.require'=>'goodsId不能为空',
        'goods_id.number'=>'goodsId为整数',
        'is_demo.in'=>'是否取样的值须为“是”和“否”',
        'is_quality_test.in'=>'是否质检的值须为“是”和“否”',
        'is_photo.in'=>'是否拍图的值须为“是”和“否”',
        'lowest_sale_price.float'=>'最低限价为小数',
        'competitor_price.float'=>'竞争对手售价为小数',
        'create_id.require'=>'添加人不能为空',
        'create_time.require'=>'添加时间不能为空',
        'update_time.require'=>'更新时间不能为空',
    ];
    protected $scene = [
        'insert'=>[
            'goods_id'=>'require|number',
            'create_time'=>'require',
            'create_id'=>'require',
        ],
        'update'=>[
            'update_time'=>'require',
            'is_demo'=>'in:0,1',
            'is_quality_test'=>'in:0,1',
            'is_photo'=>'in:0,1',
            'lowest_sale_price'=>'float',
            'competitor_price'=>'float'
        ]
    ];
}