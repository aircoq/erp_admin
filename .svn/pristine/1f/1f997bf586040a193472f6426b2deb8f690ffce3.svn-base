<?php

/**
 * Description of Aliexpress
 * @datetime 2017-5-25  17:37:46
 * @author joy
 */

namespace app\listing\validate;
use think\Validate;
class AliexpressListingValidate extends Validate{
    protected $rules = [
        ['id','require|number','id必填,且数字'],
        ['product_id','require|number','商品id必填,且数字'],
        ['group_id','require','分组Id必须'],
        ['delivery_time','require|number|egt:1|elt:60','备货期必填,取值范围:1-60'],
        ['ws_valid_num','require|number|egt:1|elt:30','备货期必填,取值范围:1-30'],
        ['subject','require|max:128|min:1','商品标题必填,长度在1-128之间英文'],
        ['product_unit','require|number','商品单位必填,且数字'],
        ['gross_weight','require|float|egt:0.001|elt:500.000','商品毛重必填,取值范围:0.001-500.000,保留三位小数'],
        ['package_length','require|number|egt:1|elt:700','商品包装长度必填,取值范围:1-700'],
        ['package_width','require|number|egt:1|elt:700','商品包装宽度必填,取值范围:1-700'],
        ['package_height','require|number|egt:1|elt:700','商品包装高度必填,取值范围:1-700'],
        ['promise_template_id','require|number','服务模板必填，且为整数'],
        ['freight_template_id','require|number','运费模板必填，且为整数'],
        ['product_price','require|float|gt:0|let:100000','商品一口价必填，取值范围:0-100000,保留两位小数'],
        ['relation_template_id','require|number|gt:0','关联信息模板必填'],
        ['relation_template_postion','require','关联信息模板所在位置必填'],
        ['custom_template_id','require|number|gt:0','自定义信息模板必填'],
        ['custom_template_postion','require','自定义信息模板位置必填'],
        ['sku','require','sku必填'],
        ['stock','require|number','最新库存数量必填，且为数字'],
        ['old_stock','require|number','修改前的库存数量必填，且为数字'],
        ['price','require|number','最新售价必填，且为数字'],
        ['old_price','require|number','修改前的售价必填，且为数字'],
        ['account_id','require|number','速卖通账号id必填，且为数字'],
        
    ];
    protected $scene = [
        'group'  =>  ['id','group_id'],
        'DeliveryTime'  =>  ['id','delivery_time'],
        'WsValidNum'=>['id','ws_valid_num'],
        'subject'=>['id','subject'],
        'product_unit'=>['id','product_unit'],
        'gross_weight'=>['id','gross_weight'],
        'package'=>['id','package_length','package_width','package_height'],
        'promiseTemplateId'=>['id','promise_template_id'],
        'freightTemplateId'=>['id','freight_template_id'],
        'productPrice'=>['id','product_price'],
        'stock'=>['product_id','sku','stock','old_stock'],
        'price'=>['product_id','sku','price','old_price'],
        'template'=>['id','relation_template_id','relation_template_postion','custom_template_id','custom_template_postion'],
        'window'=>['id','product_id','account_id'],
        
    ];
    /**
     * 
     * 校验编辑
     * @param array $post
     * @param string $scene
     * @return void|string
     */
    public  function checkEdit($post,$scene)
    {        
        foreach($post as $p)
        {
            $this->check($p,$this->rules,$scene);
            if($error = $this->getError())
            {
                return $error;
            }        
        }     
    }
}
