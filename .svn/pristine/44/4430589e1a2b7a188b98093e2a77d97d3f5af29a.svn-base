<?php

/**
 * Description of AliCategoryAuthValidate
 * @datetime 2017-6-6  14:18:23
 * @author joy
 */

namespace app\publish\validate;
use think\Validate;
class AliCategoryAuthValidate extends Validate{
   protected $rules=[
        ['id','require|number','id必填，且为数字'],
        ['account','require|array','账号必填,且数组'], 
        ['category','require|number','速卖通分类必填,且为大于0的数字'], 
        ['local_category','require|array','本地分类必填,且数组'], 
        ['account_id','require|number','账号必填,且为大于0的数字'], 
        ['category_id','require|number','速卖通分类必填,且为大于0的数字'], 
        ['local_category_id','require|number','本地分类必填,且为大于0的数字'],
        ['checked','array','选中本地分类是数组'],
        ['unchecked','array','取消选中的本地分类是数组'],
        ['name','require','模板名称必填'],
        ['module_contents','require','模板内容必填'],
        ['account_id','require|number','账号必填，且为数组'],
    ];
    protected $scene=[
        'add'  =>  ['account','category_id','local_category'],
        'edit'  =>  ['account','category_id','unchecked','checked'],
        'every'=> ['account_id','category_id','local_category_id'],
        'edit-every'=> ['account_id','category_id','local_category_id'],
        'template'=>['name','module_contents','account_id'],
        'edit_template'=>['id','name','module_contents','account_id'],
    ];
    /**
     * 验证新增
     * @param array $data
     */
    public  function checkAdd($data,$scene)
    {
        $this->check($data,$this->rules,$scene);    
         
        if($error = $this->getError())
        {
            return $error;
        }    
    }
    /**
     * 验证创建信息模板
     * @param array $data
     */
    public  function checkAddProductTemplate($data,$scene)
    {
        $this->check($data,$this->rules,$scene);    
         
        if($error = $this->getError())
        {
            return $error;
        }    
    }
}
