<?php

/**
 * Description of EbayListingValidate
 * @datetime 2017-6-20  9:59:14
 * @author joy
 */

namespace app\listing\validate;
use think\Validate;
class EbayListingValidate extends Validate{
   protected $rules = [
        ['id','require|number','id必填'],
        ['item_id','require|number','商品id必填'],
        ['title','require|max:80|min:1','商品标题必填,长度在1-80之间英文'],
        ['start_price','require|number|>=:0.75|<=:9999.99','商品一口价必填，取值范围:0.75-9999.99,保留两位小数'],
        ['buy_it_nowprice','require|float|>=:0','商品竞拍价必填，大于0'],
        ['quantity','require|number|>=:0','可售数量必填，大于等于0'],
        ['reserve_price','require|number','拍卖最低成交价必填'],
        ['store','require|number','店铺第一分类必填'],
        ['second_store','require|number','店铺第二分类必填'],
        ['replen','require|number','自动补货规则必选'],
        ['restart_rule','require|number','重上规则必选'],
        ['restart_way','require|number','重上方式必选'],
        ['restart','require|number|=:1','是否重新刊登必填'],
        ['listing_sku','require','sku必填'],
        ['promotion_id','require|number|>:0','促销模板必选'],
        ['mod_sale','require|number|>:0','销售说明模板必选'],
        ['mod_trans','require|number|>:0','物流模板必选'],
        ['mod_exclude','require|number|>:0','不送到地区模板必选'],
        ['mod_location','require|number|>:0','物品所在地模板必选'],
        ['mod_return','require|number|>:0','退货模板必选'],
        ['mod_refuse','require|number|>:0','买家限制模板必选'],
        ['mod_receivables','require|number|>:0','收款模板必选'],
        ['mod_promotion','require|number|>:0','促销模板必选'],
        ['mod_choice','require|number|>:0','备货期模板必选'],
        ['mod_pickup','require|number|>:0','自提模板必选'],
        ['mod_galley','require|number|>:0','橱窗模板必选'],
        ['mod_individual','require|number|>:0','私人物品模板必选'],
        ['mod_bargaining','require|number|>:0','买家还价模板必选'],
        ['mod_quantity','require|number|>:0','库存模板必选'],
       
    ];
    protected $scene = [
        'productPriceQuantity'=>['id','start_price','quantity','listing_sku'],
        'auctionPrice'=>['id','buy_it_nowprice','start_price','reserve_price'],
        'title'=>['id','title'],
        'shop_category'=>['store','second_store'],
        'buhuo'=>['id','replen'],
        'reshelf'=>['id','restart_rule','restart_way','restart'],
        'promotion'=>['id','promotion_id'],
        'sale'=>['id','mod_sale'],
        'common'=>['id','mod_style','mod_sale','mod_trans','mod_exclude','mod_location','mod_refuse','mod_receivables','mod_promotion','mod_choice','mod_pickup','mod_galley','mod_individual','mod_bargaining','mod_quantity'],
    ];
    
    /**
     * 
     * 校验编辑
     * @param array $post
     * @param string $scene
     * @return void|string
     */
    public  function checkSetting($post,$scene)
    {        
        foreach($post as $p)
        {
            if(isset($p['restart_rule']) && $p['restart_rule'] == 5 && empty($p['restart_count']))
            {
                return '选择[当物品卖出数量大于或等于],则售出数量必填';
            }
            
            if(isset($p['restart_way']) && $p['restart_way'] == 2 && empty($p['restart_time']))
            {
                return '选择[定时重上]，则执行时间必填';
            }
            
            $this->check($p,$this->rules,$scene);
            if($error = $this->getError())
            {
                return $error;
            }        
        }     
    }
    
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
