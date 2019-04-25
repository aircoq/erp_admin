<?php

namespace app\common\validate;

use \think\Validate;

/**
 *
 * Date: 2017/07/24
 * Time: 17:42
 */
class GoodsPreDev extends Validate
{

    protected $rule = [
        'category_id' => 'require|number',
        'dev_platform' => 'require|alpha',
        'title' => 'require|unique:Goods,name',
        'brand_id' => 'require|number',
        'tort_id' => 'number',
        'purchase_price' => 'require|float',
        'lowest_sale_price' => 'float',
        'competitor_price' => 'float',
        'advice_price' => 'require|float',
        'gross_profit' => 'require|float',
        'weight' => 'require',
        'platform_sale' => 'require',
        'description' => 'require',
        'thumb'=>'require'
    ];
    protected $message = [
        'category_id.require' => '分类必填的!',
        'category_id.number' => '分类为整形',
        'dev_platform.require' => '开发平台为必须',
        'dev_platform.alpha' => '开发平台必须为字母',
        'title.require' => '产品标题为必填项',
        'title.unique' => '产品标题已存在',
        'brand_id.require' => '品牌为必填项',
        'brand_id.number' => '品牌必须为数字',
        'tort_id.number' => '侵权方式id为数字',
        'purchase_price.require' => '采购价必填项',
        'purchase_price.float' => '采购价不能为空',
        'lowest_sale_price.float' => '最低限价为数字',
        'competitor_price.float' => '竞争对手价格为数字',
        'advice_price.require' => '建议售价为必填项',
        'advice_price.float' => '建议售价为数字',
        'gross_profit.require' => '本平台预估毛利率为必须的',
        'gross_profit.float' => '本平台预估毛利率为数字',
        'weight.require' => '产品毛重为必须的',
        'platform_sale.require' => '平台销售状态是必须的',
        'description.require' => '描述为必须的',
        'thumb.require'=>'图片必须上传'
    ];
    protected $scene = [
        'edit' => [
            'category_id' => 'number',
            'dev_platform' => 'alpha',
            'title' => 'unique:Goods,name',
            'brand_id' => 'number',
            'tort_id' => 'number',
            'purchase_price' => 'float',
            'lowest_sale_price' => 'float',
            'competitor_price' => 'float',
            'advice_price' => 'float',
            'gross_profit' => 'float'
        ],
        'insert'=>[
            'category_id',
            'dev_platform',
            'title',
            'brand_id',
            'tort_id',
            'purchase_price',
            'lowest_sale_price',
            'competitor_price',
            'advice_price' ,
            'gross_profit' ,
            'weight',
            'platform_sale',
           // 'description',
            'thumb'
        ]
    ];

}