<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use app\common\model\wish\WishReplenishment;
use service\wish\WishApi;
/**
 * @node wish 补货定时任务
 * Class WishInventory 
 * packing app\listing\task
 */
class WishInventory extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "wish补货";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "wish补货";
    }
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "joy";
    }
    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }
    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        
        set_time_limit(0);
        
        $model = new WishReplenishment();
               
        $where['a.status']=['=',0];
        
        $page =1;
        
        $pageSize=30;
        
        $fields="a.*,b.sku,d.access_token";        
        $url="https://china-merchant.wish.com/api/v2/variant/update-inventory";
        do {     
            $data = $model->getVariantAccount($where,$page,$pageSize,$fields);
            
            if(empty($data)){
                 break;
            } else{
                $this->updateInventory($data,$url,$model);
                $page++;
            }        
            
        } while (count($data)==$pageSize);
    }
    /**
     * @node 更新wish在线listing sku库存
     * @access private
     * @param array $producst 商品
     * @return void
     */
    private  function updateInventory(array $products,$url,$model)
    {
        if(is_array($products))
        {
            foreach ($products as $key => $product) 
            {              
                if($product['access_token'] && $product['sku'])
                {
                    $data = [];
                    
                    $data['sku'] = $product['sku'];
                    
                    $data['inventory'] = $product['inventory'];
                    
                    $data['access_token'] = $product['access_token'];
                     
                    $response = json_decode(curl_do($url, $data),true);
                   
                    $update=[];
                    
                    if($response['code'] == 0)
                    {
                        $update['status']=1;
                        
                    }else{
                         $update['status']=0;
                    }
                    
                    $update['runtime'] = time();
                    $update['message'] = $response['message'];
                    
                    $model->update($update,['id'=>$product['id']]);
                    
                }
            }
        }
    }
}
