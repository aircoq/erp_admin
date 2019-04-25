<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use think\Db;
use app\common\exception\TaskException;
use app\listing\service\RedisListing;
use app\listing\service\WishListingHelper;
use app\common\service\Twitter;
 
/**
 * @node 实时同步wishlisting
 * Class WishInventory 
 * packing app\listing\task
 */
class WishRsyncListing extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "实时同步wish listing";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "实时同步wishlisting";
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
        //$redis = Cache::store('wishListing');
         
        //$total = $redis->getRedis('wishRsyncListing',strtotime('-3 day'),time());
        set_time_limit(0);
        $redis = new RedisListing;   
        $total = $redis->myZRangeByScore('wishRsyncListing',strtotime('-1 day'),time()); 
         
        $helper = new WishListingHelper;
        $page = 1;
        $pageSize =10;
        
        do{
            $queues = $redis->page($total,$page,$pageSize);
            
            if(empty($queues))
            {
                break;
            }else{
                $page=$page+1;  
                self::updateListing($queues,$helper,$redis);
            }
        }while($pageSize== count($queues));         
    }
    /**
     * 实时更新在线listing
     * @param array $jobs
     */
    private static function updateListing(array $jobs,$helper,$redis)
    {
        set_time_limit(0);
        $productModel = new \app\common\model\wish\WishWaitUploadProduct();
        
        $variantModel = new \app\common\model\wish\WishWaitUploadProductVariant();
        if(is_array($jobs))
        {
            foreach ($jobs as $job) 
            {
               
                $jobInfo = $productModel->field('accountid,product_id')->where(['product_id'=>$job])->find();
                
                if($jobInfo)
                {
                    $response = $helper::retrieveProduct($jobInfo['accountid'],$jobInfo['product_id']) ;
                   
                    if($response['state'])
                    {
                        $product = $response['data']['product'];
                        $skus = $response['data']['skus'];
                        Db::startTrans();
                        try{
                            $productModel->where(['product_id'=>$product['product_id']])->update($product);
                            
                            if($productInfomation = $productModel->field('id')->where(['product_id'=>$product['product_id']])->find())
                            {
                                $pid=$productInfomation['id'];
                            }
                            
                            foreach($skus as $sku)
                            {
                                
                                if( $variantModel->where(['product_id'=>$sku['product_id'],'variant_id'=>$sku['variant_id']])->find())
                                {
                                    $variantModel->where(['product_id'=>$sku['product_id'],'variant_id'=>$sku['variant_id']])->update($sku);
                                }else{//不存在，则插入
                                    
                                    $sku['vid']=abs(Twitter::instance()->nextId(3,$jobInfo['accountid']));
                                    if($pid)
                                    {
                                        $sku['pid']=$pid;
                                    }
                                    $variantModel->insert($sku);    
                                }   
                            }
                            
                            $update=$helper->ProductStat($variantModel,['pid'=>$pid]);
                            
                            if($update)
                            {
                                 $productModel->update($update, ['id'=>$pid]); 
                            }
                            Db::commit();
                            
                            $redis->myZRem('wishRsyncListing',$job); //删除缓存
                        }catch(\Exception $exp){
                            Db::rollback();
                            //$workerId= $this->getId();
                            //$exp->recordLog($workerId);
                        }
                    }
                }          
            }
        }
    }  
   
}
