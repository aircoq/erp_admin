<?php
namespace app\publish\validate;
use think\Validate;


/**
 * @node 上传图片验证类
 * Class WishValidate
 * packing app\publish\validate
 */
class UploadImageValidate extends Validate
{
   
    protected $rules = [
        ['id','require|number|gt:0','商品id必填,且为大于0的数字'],        
        ['images','require','商品图片不能为空'],           
    ];
    
     protected $scene = [
        'net'  =>  ['id','images'],
    ];
     
     /**
      * @node 刊登数据验证
      * @access public
      * @param array $post
      * @return string
      */
    public  function  checkNetImages($post)
   {  
        
        $this->check($post,$this->rules,'net');    
         
        if($error = $this->getError())
        {
            return $error;
        }            
   } 
   
}
