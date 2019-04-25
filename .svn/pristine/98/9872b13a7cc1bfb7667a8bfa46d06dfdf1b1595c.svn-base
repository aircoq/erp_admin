<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/30
 * Time: 9:42
 */

namespace app\common\validate;


use think\Validate;
use app\common\cache\Cache;

class VirtualOrderApplyDetail extends Validate
{
    protected $sku = [];
    protected $rule = [
        ['virtual_order_apply_id', 'require', '虚拟订单不能为空'],
        ['sku_id', 'require|number|checkSkuId', 'sku_id不能为空|sku_id为整数|sku_id不存在'],
        ['sku', 'require|checkSku', 'sku不能为空|sku不存在'],
        ['account_id', 'require|number', '账户id不能为空|账户ID为整数'],
        ['account_name', 'require', '店铺名称不能为空！'],
        ['keyword', 'require', '关键字不能为空！'],
        ['product_location', 'require', '产品位置不能为空！'],
//        ['product_link', 'require|url', '产品链接不能为空！|产品链接为url'],
//        ['is_collection_product', 'require', '是否收藏产品不能为空！'],
//        ['is_stars', 'require', '是否打五星不能为空！'],
//        ['is_collection_shop', 'require', '是否收藏店铺不能为空！'],
        ['estimate_cost', 'float', '估算费用为小数'],
    ];

    protected function checkSkuId($value,$rule, $data){
        $this->sku =Cache::store('goods')->getSkuInfo($value);
        if($this->sku){
            return true;
        }
        return false;
    }
    protected function checkSku($value,$rule, $data){
        if($this->sku){
            if($this->sku['sku']==$value){
                return true;
            }
        }
        return false;
    }


}