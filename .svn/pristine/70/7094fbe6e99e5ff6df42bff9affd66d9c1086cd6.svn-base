<?php

namespace app\listing\validate;
use think\Validate;

class AmazonListingValidate extends Validate
{
    protected $rules = [
        ['id','require|number','id必填,且数字'],
        ['amazon_listing_id','require','Amazon Listing-Id必填'],
        ['account_id','require|number','Amazon账号id必填，且为数字'],
    ];
    public $scene = [
        'quantity'=>['amazon_listing_id','account_id','new_value','old_value'],
        'price'=>['amazon_listing_id','account_id','new_value','old_value'],
        'itemname'=>['amazon_listing_id','account_id','new_value','old_value'],
        'description'=>['amazon_listing_id','account_id','new_value','old_value'],
        'fulfillment_type'=>['amazon_listing_id','account_id','new_value','old_value'],
        'seller_status'=>['amazon_listing_id','account_id','new_value','old_value'],
        'image'=>['amazon_listing_id','account_id','new_value','old_value'],
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
