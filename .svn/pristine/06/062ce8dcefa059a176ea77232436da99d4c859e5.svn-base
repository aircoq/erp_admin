<?php

/**created by NetBeans
 * author:joy
 * date:2017-04-15
 * time:11:33
 */
namespace app\listing\task;

use app\common\model\wish\WishActionLog;
use app\index\service\AbsTasker;
use think\Cache;
use think\Db;
use service\wish\WishApi;
use app\listing\service\WishListingHelper;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\cache\Cache as myCache;


/**
 * @node 更新wish listing data
 * Class WishListingUpdate
 * packing app\listing\task
 */
class WishListingUpdate extends AbsTasker{
    
    public function getName()
    {
        return "更新wish在线listing资料";
    }

    public function getDesc()
    {
        return "更新wish在线listing资料";
    }

    public function getCreator()
    {
        return "joy";
    }
    
    public function getParamRule()
    {
        return [];
    }
     
    public  function execute()
    {
        set_time_limit(0);
        
        $helper = new WishListingHelper;
        
        //$redis =  Cache::store('redis')->handler();
                 
        //获取1天前加入更新的,按时间先后顺序获取
        //$product_ids = $redis->ZRANGEBYSCORE('wishUpdateListing', strtotime('-1 day'),time());
        
        $redis = new \app\listing\service\RedisListing;             
        $product_ids = $redis->myZRangeByScore('wishUpdateListing',strtotime('-1 day'),time());
         
        if(is_array($product_ids) && !empty($product_ids))
        {
            foreach($product_ids as $product_id)
            {
                $options['type']='file';
                Cache::connect($options);
                $product_data = Cache::get('wishUpdateListingProductId:'.$product_id);
                 
                if($product_data)
                {
                    $accountInfo = $helper::getAccount(['account_name'=>$product_data['account_name']],'access_token'); 
                    $api = WishApi::instance(['access_token'=>$accountInfo['access_token']])->loader("Product");
                    //如果存在更新产品数据
                    if(isset($product_data['product']) && $product_data['product'])
                    {
                        $product=$product_data['product'];
                        
                        $product['access_token']=$accountInfo['access_token'];
                        
                        $response = $api->updateProduct($product);
                        
                        if($response['state']==true)
                        {
                           //如果更新产品信息成功，则将产品数据清除
                            $log=[];
                            $log['run_time']=time();
                            $log['code']=1;
                            $log['message']=$response['message'];
                            (new WishActionLog())->where(['product_id'=>$product['id'],'code'=>0])->order('id ASC')->limit(1)->update($log);
                            unset($product);
                        }     
                    }
                    
                    //如果存在更新sku数据
                    if(isset($product_data['vars']) && $product_data['vars'])
                    {
                        $skus = $product_data['vars'];
                        foreach ($skus as $k=> $sku) 
                        {
                            $sku['access_token']=$accountInfo['access_token'];
                        
                            $response = $api->updateVariation($sku);

                            if($response['state']==true)
                            {
                                //如果更新sku数据成功
                                $log=[];
                                $log['run_time']=time();
                                $log['code']=1;
                                $log['message']=$response['message'];
                                Db::table('wish_action_log')->where(['product_id'=>$sku['product_id'],'variant_id'=>$sku['variant_id'],'code'=>0])->order('id ASC')->update($log);
                                unset($skus[$k]);
                            }     
                        }
                    }                    
                    //如果产品信息和sku信息都不存在，则删除缓存文件，清除redis缓存
                     if(isset($product_data['product']) && isset($product_data['vars']))
                     {
                         if(empty($product) && empty($skus))
                         {
                             Cache::rm('wishUpdateListingProductId:'.$product_id);//删除缓存文件
                             $redis->myZRem('wishUpdateListing',$product_id);
                         }elseif(empty ($product)){
                            unset($product_data['product']);
                         }elseif(empty ($skus)){
                            unset($product_data['vars']);
                         }
                         
                     }elseif(isset($product_data['product'])){
                         if(empty($product))
                         {
                             Cache::rm('wishUpdateListingProductId:'.$product_id);//删除缓存文件
                             $redis->myZRem('wishUpdateListing',$product_id);
                         }
                         
                     }elseif(isset($product_data['vars'])){
                         if(empty($sku))
                         {
                             Cache::rm('wishUpdateListingProductId:'.$product_id);//删除缓存文件
                             $redis->myZRem('wishUpdateListing',$product_id);
                         }
                    }
                }
            }
        }   
    }  
}
