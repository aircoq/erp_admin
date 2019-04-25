<?php
namespace app\listing\validate;
use think\Validate;
use app\listing\service\WishListingHelper;


/**
 * @node wish listing验证类
 * Class WishValidate
 * packing app\publish\validate
 */
class WishListingValidate extends Validate
{
    protected $product=array();
    protected  $variant=array();
   
    
    protected $rules = [
        ['goods_id','require|number','商品id必填,且数字'],
        ['id','require|number','id必填，且为数字'],
        ['uid','require|number','用户id必须，且数字'],
        ['product_id','require','商品id必须'],
        ['variant_id','require','sku id必填'],
        ['tags','require','商品tags必填'],      
        ['parent_sku','require','SPU必填'],
        //['main_image','require','商品主图main_image必填'],
        ['price','require|number|gt:0','商品售价必填,且为大于0的数字'],
        ['shipping','require|number|egt:0','运费必填，大于等于0'],
        ['shipping_time','require','发货时间必填'],
        ['inventory','require|number|gt:0|elt:500000','商品库存inventory必填,且大于0小于等于500000'],
        //['variant','require','商品多属性（变体）必填'],
        //['zh_name','require','商品中文标题必填'],   
        ['vars','require','vars必填,且不能为空'],
        ['accountid','require|number|gt:0','刊登账号必填,且为大于0的数字'],
        ['name','require','刊登标题必填'],
        ['images','require|array','商品相册不能为空，且为数组'],
        ['main_image','require','sku图片不能为空'],
        ['description','require','商品描述详情description必填'],
        ['country','require|max:2','国家必须，且只能为2个字符'],
        ['queue','require','请选中要同步的listing再提交'],
        //['queue','array','queue为数组'],
        
        ['sku','require','sku不能为空'],
        ['size_color','require','color和size不能都为空'],
        ['price','require|number|gt:0','销售价不能为空'],
        ['price','number','销售价只能是数字'],
        ['price','gt:0','销售必须大于0'],
        ['shipping','require','运费不能为空'],
        ['shipping_time','require','发货期不能为空'],
        ['inventory','require|number|gt:0','库存不能为空'],
        ['inventory','number','库存只能是数字'],
        ['inventory','gt:0','库存必须大于0'],
        //['main_image','require','sku图片不能为空'],
     
    ];
    
     protected $scene = [
        'add'  =>  ['goods_id','uid','parent_sku','zh_name','vars'],
        'var'=>['accountid','name','inventory','price','tags','description','shipping','shipping_time','images'],
        'variant'=>['sku','color','size','price','shipping','shipping_time','inventory'],
        'buhuo'=>['product_id','variant_id','inventory','uid'],
        'shipping'=>['product_id','country','shipping'],
        'rsyncListing'=>['queue'],
        'edit'=>['sku','size_color','price','shipping','shipping_time','inventory'],
        'name'=>['product_id','name'],
        'tags'=>['product_id','tags'],
        'price'=>['variant_id','price'],
        'shipping'=>['variant_id','shipping'],
        'shipping_time'=>['variant_id','shipping_time'],
        'inventory'=>['variant_id','inventory'],
        'msrp'=>['variant_id','msrp'],
 
    ];
    /**
     * 校验批量编辑单个字段编辑
     * @param array $post 批量修改的数据
     * @param string $type 修改的字段
     * @return string 
     */
    public function checkBatchEdit($post,$type)
    {
        $helper = new WishListingHelper;
        
        $product_fields = $helper->productAllowUpdateFields();
        $variant_fields = $helper->variantAllowUpdateFields();
        if(is_array($post))
        {
            if(in_array($type,$product_fields ) || in_array($type, $variant_fields))
            {
                foreach($post as $p)
                {           
                    
                   $this->check($p,$this->rules,$type);
                   if($error = $this->getError())
                   {
                       return $error;
                   }
                }   
            }else{
                $error ='要更新的字段不存在';
                 return $error;
            }
             
        }  
    }
    
     /**
      * @node 校验批量修改数据合法性
      * @param array $post 校验数据
      * @return string $error 
      */
    public function checkEdit($post)
    {
        foreach($post as $p)
        {
            $p['size_color']=$p['size'].$p['color'];
            
            $this->check($p,$this->rules,'edit');
            if($error = $this->getError())
            {
                return $error;
            }
                 
        }     
    }
    /**
     * @node 刊登数据验证
     * @access public
     * @param array $post
     * @return string
     */
    public  function  checkRsyncListing($post=array())
   {  
        $this->check($post,$this->rules,'rsyncListing');    
         
        if($error = $this->getError())
        {
            return $error;
        }            
   }
   
     /**
      * @node 刊登数据验证
      * @access public
      * @param array $post
      * @return string
      */
    public  function  checkData($post=array())
   {  
        $this->check($post,$this->rules,'add');    
         
        if($error = $this->getError())
        {
            return $error;
        }            
   }
   
   
   /**
    * @node 验证刊登数据中的var变量数据合法性
    * @access public
    * @param array $post
    * @param string $scene
    * @return string
    */
   public function  checkVars($post = array(),$scene='var')
   {   
       foreach ($post as $key => $var) 
       {     
           if(is_array($var))
           {
               $error = $this->checkVar($var);
           }
           if($error)
           {
               return '账号['.$post[$key]['account_code'].']'.$error;
           }
       }      
   }
   /**
    * 校验每个账号的数据
    * @param type $var
    * @return string
    */
   protected  function checkVar($var)
   {
        if(isset($var['variant']) && $var['variant'])
        {
           $error = $this->checkVariant($var['variant'], 'variant');
           if($error)
           {
               return $error;
           }
        }
       
        if(isset($var['images']) && empty($var['images']))
        {
            $error='商品相册不能为空';
            return $error;
        }
        
        $this->check($var,$this->rules,'var');
        $error = $this->getError();

       if($error)
       {
           return $error;
       }else{
           return '';
       }
   }
   
   /**
    * @node 验证刊登数据中的var变量数据合法性
    * @access public
    * @param array $post
    * @param string $scene
    * @return string
    */
   /*public function  checkVar($post = array(),$scene='var')
   {   
       foreach ($post as $key => $var) 
       {     
            if(is_array($var))
            {
                $this->checkVar($var,'var');
            }
            
            if($key == 'variant')
            {   
                $this->checkVariant(@$var['variant'],'variant');
            }else{                
                $this->check($var,$this->rules,$scene);  
            } 
            
            if($error = $this->getError())
            {
                return $error;
            }    
       }      
   }*/
   
   /**
    * 校验sku信息
    * @param type $post
    * @param type $scene
    * @return string
    */
   protected  function checkVariant($post,$scene='variant')
   {
       
       foreach ($post as $key => $var) 
       {
            if(count($post)>1)
            {
                if(empty($var['color']) && empty($var['size']))
                {
                    return '['.$var['sku'].']color和size不能同时为空';
                }
            }

            $this->check($var,$this->rules,$scene);
            if($error = $this->getError())
            {
                return '['.$var['sku'].']'.$error;
            }  
       }
   }

   
   /**
    * @node 验证wish在线listing补货功能提交数据的合法性
    * @access public
    * @param array $post
    * @return string
    */
    public  function checkBuhuo($post)
   {
        $this->check($post,$this->rules,'buhuo');    
         
        if($error = $this->getError())
        {
            return $error;
        }   
   }
   
   /**
    * @node 验证wish修改运费提交数据的合法性
    * @access public
    * @param array $post
    * @return string
    */
    public  function checkShipping($post)
   {
        $this->check($post,$this->rules,'shipping');    
         
        if($error = $this->getError())
        {
            return $error;
        }   
   }
   
}
