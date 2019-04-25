<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/22
 * Time: 13:42
 */

namespace app\publish\validate;

use think\Exception;
use think\Validate;

class LazadaListingValidate extends Validate
{
    protected $rules=[
        ['id','require|number','平台SKU ID必填'],
        ['account_id','require|number|gt:0','账号ID必填,且为大于0的数字'],
        ['item_sku','require','平台sku必填'],
        ['category_id','require|number','分类id必填'],
        ['goods_id','require|number','商品id必填'],
        ['name','require|min:2|max:255','标题至少2个字符,但不大于255个字符'],
        ['description','require|min:6|max:2500','内容描述至少6个字符,但不大于25000个字符'],
        ['short_description','require|min:6|max:2500','短描述至少6个字符,但不大于25000个字符'],

        ['variation_sku','require','变体sku必填'],
        ['original_price','require|number|gt:0', '商品原价必填,且为大于0的数字'],
        ['price','require|number|gt:0', '商品售价必填,且为大于0的数字'],
        ['quantity','require|number','商品可售量必填,且只能是数字'],
        ['package_width','require|number|gt:0','包裹宽度必填,且为大于0的数字'],
        ['package_height','require|number|gt:0','包裹高度必填,且为大于0的数字'],
        ['package_length','require|number|gt:0','包裹长度必填,且为大于0的数字'],
        ['package_weight','require|number|gt:0','包裹重量必填,且为大于0的数字'],
        ['tax_class','require|gt:50','税必填,且不大于50个字符'],
    ];

    protected $scene = [
        'edit'=>['name', 'category_id', 'description', 'short_description'],
        'variant'=>['original_price', 'price', 'quantity', 'package_width', 'package_height', 'package_length', 'package_weight', 'tax_class'],
        ];

    public function checkEdit($data, $scene = 'edit')
	{
	    foreach ($data as $k=>$v) {
            $this->check($v, $this->rules, $scene);
            if($error = $this->getError()) {
                return $error;
            }
            foreach ($v['variant'] as $kk=>$vv) {
                $this->check($vv, $this->rules, 'variant');
                if($error = $this->getError()) {
                    return $error;
                }
            }
        }
	}


}