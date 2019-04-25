<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use service\wish\WishApi;
use think\Db;
use app\common\exception\TaskException;
use app\listing\service\WishListingHelper;
/**
 * @node update wish express物流
 * Class WishInventory 
 * packing app\listing\task
 */
class WishUpdateShipping extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "同步wish平台设置的wish express";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "同步wish平台设置的wish express";
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
        
        $page =1;
        $pageSize=30;
        $fields="e.*,a.access_token";
	    //$where['access_token']=array('<>','');
	    $where['is_invalid']=array('eq',1);
	    $where['state']=array('<>',1);    //没有执行成功的
        do {     
            $products = Db::table('wish_express')->alias('e')
                        ->join('wish_wait_upload_product p','e.product_id=p.product_id' , 'LEFT')
                        ->join('wish_account a','p.accountid=a.id','LEFT')
                        ->field($fields)->where($where)->page($page, $pageSize)->select();

            if(empty($products)){
                 break;
            } else{
                self::updateMultiShipping($products);
                $page++;
            }           
        } while (count($products)==$pageSize);
    }
    
    private static function updateMultiShipping(array $products)
    {
        if(is_array($products) && $products)
        {
            $service = new WishListingHelper;
            foreach($products as $product)
            {

                Db::startTrans();
                try{
                    $data = $service->splitMultiShippingData($product);

                    if($product['access_token'])
                    {
	                    $response= WishApi::instance(['access_token'=>$product['access_token']])->loader('Product')->updateMultiShipping($data);

	                    if($response['state']==true)
	                    {
		                    $update['state']=1; //执行成功
	                    }else{
		                    $update['state']=2; //执行失败
	                    }

	                    $update['id']=$product['id'];
	                    $update['run_time']=time();
	                    $update['message']=$response['message'];
	                    Db::table('wish_express')->update($update);
	                    
                    }
                    Db::commit();

                }catch(\Exception $exp){
                    Db::rollback();
	                throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
                }    
            }
        }
    }
    
    
   
}
