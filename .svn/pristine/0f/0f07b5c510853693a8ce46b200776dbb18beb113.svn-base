<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\model\wish;
use think\Model;
/**
 * wish补货模型
 *
 * @author Administrator
 */
class WishReplenishment extends Model{
    public function existOne(array $where)
    {
        if($this->get($where))
        {
            return true;
        }else{
            return false;
        }
    }
    
    public function addOne(array $data)
    {
        return $this->insertGetId($data);
    }
    
    public  function getVariantAccount($where=array(),$page=1,$pageSize=30,$fields="*")
    {
        return $this->alias('a')->join('wish_wait_upload_product_variant b','a.variant_id=b.variant_id','LEFT')
                                ->join('wish_wait_upload_product c','c.id=b.pid','LEFT')
                                ->join('wish_account d','d.id=c.accountid','LEFT')
                                ->field($fields)
                                ->page($page,$pageSize)
                                ->where($where)
                                ->order('a.id desc')
                                ->select();
    }
    
}
