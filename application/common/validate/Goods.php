<?php

namespace app\common\validate;

use \think\Validate;

/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/01/16
 * Time: 14:49
 */
class Goods extends Validate
{
    protected $rule = [
        'name' => 'require|unique:Goods,name|max:120',
        'spu' => 'require|unique:Goods,spu',
        'brand_id' => 'require|number',
        'tort_id' => 'require|number',
        'cost_price' => 'require|float',
        'retail_price' => 'require|float',
        'transport_property' => 'require',
        'net_weight' => 'require|number',
        'weight', 'require|number',
        'is_packing' => 'require',
        'packing_id' => 'require',
        'unit_id' => 'require|number',
        'warehouse_id' => 'require|number',
        'is_multi_warehouse' => 'require|in:1,0',
        'description' => 'require',
        'category_id' => 'require',
        'dev_platform_id' => 'require|in:0,1,2,3,4,5,6',
        'channel_id' => 'require|in:1,2,3,4'


    ];
    protected $message = [
        'id.require' => 'GoodsId不能为空',
        'id.number' => 'GoodsId为整形',
        'name.require' => '产品名称不能为空',
        'name.unique' => '该产品名称已存在',
        'name.max' => '产品名称不能超过120长度',
        'spu.require' => 'spu不能为空',
        'spu.unique' => '该spu已存在',
        'brand_id.require' => '品牌不能为空',
        'brand_id.number' => '品牌id为整数',
        'tort_id.require' => '产品侵权风险不能为空',
        'tort_id.number' => '产品侵权风险为整数',
        'cost_price.require' => '采购价不能为空',
        'cost_price.float' => '采购价为小数',
        'retail_price.require' => '建议零售价不能为空',
        'retail_price.float' => '建议零售价为小数',
        'transport_property.require' => '物流属性不能为空',
        'net_weight.require' => '产品毛重不能为空',
        'net_weight.number' => '产品毛重为整数',
        'weight.require' => '产品重量为整数',
        'weight.number' => '产品重量为整数',
        'is_packing.require' => '是否包含包装不能为空',
        'packing_id.require' => '包装材料不能为空',
        'unit_id.require' => '产品单位不能为空',
        'unit_id.number' => '单位ID为整数',
        'warehouse_id.require' => '默认仓库不能为空',
        'warehouse_id.number' => '默认仓库ID为整数',
        'is_multi_warehouse.require' => '是否存在于多仓库',
        'is_multi_warehouse.in' => '是否存在于多仓库的值只能为“是”和“否”',
        'description.require' => '描述不能为空',
        'category_id.require' => '分类不能为空',
        'category_id.number' => '分类ID为整数',
        'channel_id.require' => '渠道id不能为空',
        'channel_id.in' => '渠道id为[1,2,3,4]',
        'channel_id.number' => '渠道id为数字',
        'dev_platform_id.require' => '开发部门不能为空',
        'dev_platform_id.in' => '开发部门只能为[“AliExpress部“，“Amazon部”，“eBay部”，“Wish部”，“服装事业部”,“LED事业部”,“女装事业部”]',
        'gross_profit.require' => '平台毛利率不能为空',
        'source_url.url' => '平台链接地址必须为url',
        'thumb.require'=>'图片不能为空',
        'depth.require'=>'长度不能为空',
        'depth.number'=>'长度仅为数字',
        'width.require'=>'宽度不能为空',
        'width.number'=>'宽度仅为数字',
        'height.require'=>'高度不能为空',
        'height.number'=>'高度仅为数字',
        'update_time.require'=>'更新时间不能为空',
        'create_time.require'=>'创建时间不能为空',
        'developer_id.require'=>'开发员不能为空',
        'developer_id.number'=>'开发员须为数字',
        'volume_weight.require'=>'体积重是必须的',
    ];
    protected $scene = [
        'import' => ['name', 'category_id', 'spu', 'transport_property', 'weight', 'warehouse_id', 'dev_platform_id', 'channel_id'],//通过excell导入
        'dev' => ['spu', 'name', 'category_id', 'brand_id', 'tort_id', 'retail_price', 'transport_property', 'channel_id'],//通过预开发流程
        'update' => [
            'name' => 'max:120',
            'brand_id' => 'number',
            'tort_id' => 'number',
            'retail_price' => 'number',
            'net_weight' => 'number',
            'weight' => 'number',
            'unit_id' => 'number',
            'warehouse_id' => 'number',
            'is_multi_warehouse' => 'in:1,0',
            'channel_id' => 'in:1,2,3,4',
        ],
        'dev_baseInfo_insert' => [
            'category_id' => 'require|number',
            'channel_id'=>'require|number',
            'developer_id'=>'require|number',
            'name' => 'require|unique:Goods,name|max:120',
            'brand_id' => 'require|number',
            'tort_id' => 'require|number',
            'cost_price' => 'require|float',
            'retail_price' => 'require|float',
            'gross_profit' => 'require',
            'transport_property' => 'require',
            'depth' => 'require|number',
            'width' => 'require|number',
            'height' => 'require|number',
            'volume_weight' => 'require',
            'weight' => 'require|number',
            'is_packing' => 'require',
            'warehouse_id' => 'require|number',
            'is_multi_warehouse' => 'require|in:1,0',
            'create_time'=>'require'
        ],

        'dev_baseInfo_update' => [
            'id' => 'require|number',
            'category_id' => 'number',
            'channel_id'=>'number',
            'developer_id'=>'number',
            'name' => 'unique:Goods,name|max:120',
            'brand_id' => 'number',
            'tort_id' => 'number',
            'cost_price' => 'float',
            'retail_price' => 'float',
            'depth' => 'number',
            'width' => 'number',
            'height' => 'number',
            'weight' => 'number',
            'warehouse_id' => 'number',
            'is_multi_warehouse' => 'in:1,0',
            'update_time'=>'require'
        ]
    ];
}