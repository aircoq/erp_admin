<?php
namespace app\publish\validate;
use think\Validate;
/**
 * Created by ZenStudio.
 * User: Hot-Zr
 * Date: 2017年3月20日
 * Time: 09：25
 * Info: 用来处理 对第三方速卖通发送数据的一些验证
 */
class ExpressValidate extends Validate
{
    protected $rule = [
        ['client_id','require|number','应用KEY不能为空|应用KEY必须为数字类型'],
        ['client_secret','require','应用密钥不能为空'],
        ['access_token','require','access_token不能为空'],
        ['refresh_token','require','access_token不能为空'],
        ['account_id','require|number','商户ID不能为空|账户ID必须为数字类型'],
        ['category_id','require|number','分类ID不能为空|分类ID必须为数字类型'],

        /*这里大部分为刊登的验证*/
        ['goods_id','require|number','产品ID不能为空|产品ID必须为数字类型'],
        ['category_id','require|number','分类ID不能为空|分类ID必须为数字类型'],
        ['brand_id','number','品牌ID必须为数字类型'],
        ['salesperson_id','require|number','销售员ID不能为空|销售员ID必须为数字类型'],
        ['warehouse_id','require|number','仓库ID不能为空|仓库ID必须为数字类型'],
        ['subject','require','标题不能为空'],
        ['delivery_time','require|between:1,60','备货期不能为空|备货期必须是1-60天'],
        ['promise_template_id','number','服务模板ID必须为数字'],
        ['freight_template_id','number','运费模板ID必须为数字'],
        ['imageurls','require','商品主图不能为空！'],
        ['product_unit','number','商品单位必须为数字！'],
        ['is_plan_publish','number','定时刊登状态必须为数字！'],
        ['plan_publish_time','number','定时刊登时间必须为数字！'],
        ['package_type','number','打包销售状态必须为数字！'],
        ['lot_num','number','每包件数必须为数字！'],
        ['package_length','float','长度必须为数字！'],
        ['package_width','float','宽度必须为数字！'],
        ['package_height','float','高度必须为数字！'],
        ['gross_weight','float','重量必须为数字！'],
        ['is_pack_sell','number','自定义计重状态必须为数字！'],
        ['base_unit','number|between:0,1000','不增运费件数必须为数字！|不增运费数值为0-1000内'],
        ['add_unit','number|between:0,1000','每次件数增数必须为数字！|每次件数增数数值为0-1000内'],
        ['add_weight','float|between:0.000,500.000','每次重量增数必须为数字！|每次重量增数数值为0.000-500.000内'],
        ['ws_valid_num','number|between:1,30','有效期天数必须为数字！|有效期天数为1-30天内'],
        ['bulk_order','number|min:1','批发最小数量必须为数字！|批发最小数量必须大于'],
        ['bulk_discount','number|between:1,99','批发折扣必须为数字！|批发折扣取值范围是1-99'],
        ['reduce_strategy','require|in:place_order_withhold,payment_success_deduct','库存扣减策略不能为空|库存扣减方式错误！'],
        ['group_id','number','商品分组必须为数字'],
        ['detail','require','商品描述不能为空'],
        
        ['sku_price','require|float','SKU价格不能为空|SKU价格必须为数字！'],
        ['sku_code','require','SKU编码不能为空！'],
        
        //['sku_property_id','require|number','SKU属性ID不能为空！|SKU属性ID必须为数字！'],
        //['property_value_id','require|number','SKU属性值ID不能为空！|SKU属性值ID必须为数字！'],

        ['sku_ids','require','必须选择SKU'],
        ['name', 'require','分组名称不能为空'],
    ];

    protected $scene = [
        'prohibited'    =>  ['category_id','account_id'],
        'sku_info'      =>  ['category_id','sku_ids'],
        'getexpress'    =>  ['client_id','client_secret','access_token','refresh_token'],
        'accountId'     =>  ['account_id'],
        'categoryId'    =>  ['category_id'],
        'listing'       =>  ['skuPrice','skuCode'],
        //'sku_val'       =>  ['sku_property_id','property_value_id'],
        'publish'       =>  [
            'account_id',
            'goods_id',
            'category_id',
            'brand_id',
            //'salesperson_id',
            //'warehouse_id',
            'subject',
            'delivery_time',
            'promise_template_id',
            'freight_template_id',
            'imageurls',
            'product_unit',
            'is_plan_publish',
            'plan_publish_time',
            'package_type',
            'lot_num',
            'package_length',
            'package_width',
            'package_height',
            'gross_weight',
            'is_pack_sell',
            'base_unit',
            'add_unit',
            'add_weight',
            'ws_valid_num',
            'bulk_order',
            'bulk_discount',
            'reduce_strategy',
            'group_id',
            'detail',
        ],
        'edit'       =>  [
            'account_id',
            'goods_id',
            'category_id',
            'brand_id',
            //'salesperson_id',
            //'warehouse_id',
            'subject',
            'delivery_time',
            'promise_template_id',
            'freight_template_id',
            'imageurls',
            'product_unit',
            'is_plan_publish',
            'plan_publish_time',
            'package_type',
            'lot_num',
            'package_length',
            'package_width',
            'package_height',
            'gross_weight',
            'is_pack_sell',
            'base_unit',
            'add_unit',
            'add_weight',
            'ws_valid_num',
            'bulk_order',
            'bulk_discount',
            'reduce_strategy',
            //'group_id',
            'detail',
        ],

        //速卖通区域分组添加
        'add_region_group' => ['name'],
    ];
    
}
