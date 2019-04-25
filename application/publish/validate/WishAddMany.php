<?php

/**
 * Description of WishAddMany
 * @datetime 2017-5-11  10:55:03
 * @author joy
 */

namespace app\publish\validate;
use think\Validate;
class WishAddMany extends Validate{
     protected $rules = [
        ['goods_id','require|number','商品id必填,且为大于0的数字'],        
        ['uid','require|number|gt:0','用户id必须，且为大于0的数字'],
        ['product_id','require','商品id必须'],
        ['variant_id','require','sku id必填'],
        ['parent_sku','require','SPU必填'],
        //['main_image','require','商品主图main_image必填'],
        ['price','require|number|gt:0','商品售价必填,且为大于0的数字'],
        ['shipping','require|number|egt:0','运费必填，大于等于0'],
        ['shipping_time','require','发货时间必填'],
        ['inventory','require|number|gt:0|elt:500000','商品库存inventory必填,且大于0小于等于500000'],
        //['variant','require','商品多属性（变体）必填'],
        //['zh_name','require','商品中文标题必填'],   
        ['sku','require','vars必填,且不能为空'],
        ['account_id','require|number|gt:0','刊登账号必填,且为大于0的数字'],
        ['name','require','刊登标题必填'],
        ['images','require|array','商品相册不能为空'],
        ['main_image','require','sku图片不能为空'],
        ['description','require','商品描述详情description必填'],
        ['tags','require','商品tags必填'],    
    ];
    
     protected $scene = [
        'addMany'  =>  ['uid','account_id','sku'],
        'sku'=>['images','goods_id','parent_sku','name','tags','variant','description'],
        'variant'=>['sku','inventory','price','shipping_time','shipping'],
    ];
     
     /**
      * @node 刊登数据验证
      * @access public
      * @param array $post
      * @return string
      */
    public  function  checkData($post=array())
   {  
        
        $this->check($post,$this->rules,'addMany');    
         
        if($error = $this->getError())
        {
            return $error;
        }
        
        if(isset($post['sku']))
        {
            foreach ($post['sku'] as $key => $sku) 
            {
                $this->checkSku($sku);
                
                if($error = $this->getError())
                {
                    return 'spu:['.$sku['parent_sku'].']'.$error;
                }
                
                if(isset($sku['variant']))
                {   
                    foreach ($sku['variant'] as $variant) 
                    {
                        if(count($sku['variant'])>1) //如果变体数目多于1个，则必填color或size
                        {
                            if($variant['size']=='' && $variant['color']=='')
                            {
                                return 'sku:['.$variant['sku'].']'.'color和size不能同时为空';
                            }
                        }
                        $this->checkVariant($variant);
                        
                        if($error = $this->getError())
                        {
                            return 'sku:['.$variant['sku'].']'.$error;
                        }
                    }
                }      
            }
        }
   }
   
   /**
    * 校验商品数据
    * @param array $post
    * @return string|bool
    */
   public  function checkSku($post)
   {
        $this->check($post,$this->rules,'sku');    
         
        if($error = $this->getError())
        {
            return $error;
        }
   }
   /**
    * 校验sku数据
    * @param array $post
    * @return string|bool
    */
   public function checkVariant($post)
   {
        $this->check($post,$this->rules,'variant');    

        if($error = $this->getError())
        {
             return $error;
        }
   }
}
