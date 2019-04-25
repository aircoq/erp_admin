<?php
namespace app\listing\task;
use app\index\service\AbsTasker;
use think\Db;
use app\common\exception\TaskException;
use app\listing\service\RedisListing;
use service\wish\WishApi;
 

class WishRsyncEditListing extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "wish更新修改的listing";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "wish更新修改的listing";
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
        //$redis = Cache::store('wishListing'); 
        //$total = $redis->getRedis('wishUpdateDataListing',strtotime('-3 day'),time());
        
        $redis = new RedisListing;
        
        $total = $redis->myZRangeByScore('wishUpdateDataListing',strtotime('-1 day'),time()); 
       
        $page = 1;
        $pageSize =30;
        do{
            $queues = $redis->page($total,$page,$pageSize);
             
            if(empty($queues))
            {
                break;
            }else{
                $page=$page+1;  
                self::updateEditListing($queues,$redis);
            }
        }while($pageSize== count($queues));         
    }
    
    /**
     * 实时更新在线listing
     * @param array $jobs
     */
    private static function updateEditListing(array $jobs,$redis)
    {
        set_time_limit(0);
        
        if(is_array($jobs))
        {
            foreach ($jobs as $job) 
            {   
                $jobInfo = Db::table('wish_wait_upload_product')->alias('p')->join('wish_account a','p.accountid=a.id','LEFT')->field('accountid,product_id,lock_update,lock_product,access_token')->where(['product_id'=>$job])->find();
                
                if($jobInfo)
                {
                    if($jobInfo['lock_update']==1  ) //更新了资料，且更新了商品信息
                    {
                        $access_token = $jobInfo['access_token'];
                        
                        $api = WishApi::instance(['access_token'=>$access_token])->loader("Product");
                        
                        $skus = Db::table('wish_wait_upload_product_variant')->field('variant_id,sku,inventory,price,shipping,enabled,size,color,msrp,shipping_time,main_image')->where(['product_id'=>$job,'lock_variant'=>1])->select();
                         
                        if($skus)
                        {
                            self::updateVariantData($api,$skus,$access_token,$job);
                        }
                        
                        if($jobInfo['lock_product']==1)
                        {
                            $product = Db::table('wish_wait_upload_product')->field('product_id id,name,description,tags,brand,upc,main_image,original_images extra_images')->where(['product_id'=>$job])->find();
                             
                            self::updateProductData($api,$product,$access_token);
                        }   
                    }
                    
                    $lock = Db::table('wish_wait_upload_product')->field('lock_update')->where(['product_id'=>$job])->find();                   
                    if($lock['lock_update'] == 0)  //如果更新标识为0，则更新成功了，删除缓存
                    {
                       $redis->myZRem('wishUpdateDataListing',$job);
                    }   
                }          
            }
        }
    }
    
    /**
     * 更新在线listing商品数据
     * @param type $api
     * @param type $product
     * @param type $access_token
     */
    private static function updateProductData($api,$product,$access_token)
    {
        set_time_limit(0);
        if($api && $product && $access_token)
        {
            $product['access_token'] = $access_token;
             
            $response = $api->updateProduct($product);     
            
            if($response['state']==true)
            {
               $update['lock_product']=0;
               $update['lock_update']=0;
               $update['update_message']=$response['message'];
            }else{
                $update['update_message']=$response['message'];
            } 
            
            Db::startTrans();
            try{
                Db::table('wish_wait_upload_product')->where(['product_id'=>$product['id']])->update($update);
                Db::commit();
            }catch(\Exception $ex){
                Db::rollback();
                var_dump($ex->getMessage());
            }
        }
        
    }
    /**
     * 更新sku数据
     * @param type $api
     * @param type $skus
     * @param type $access_token
     * @param type $product_id
     */
    private static function updateVariantData($api,$skus,$access_token,$product_id)
    {
        set_time_limit(0);
        if(is_array($skus))
        {
            foreach ($skus as $k=> $sku) 
            {          
                $sku['access_token'] = $access_token;
                $response = $api->updateVariation($sku);
                
                if($response['state']==true)
                {
                   $update['lock_variant']=0;
                   $update['update_msg']=$response['message'];
                }else{
                    $update['update_msg']=$response['message'];
                }   
                Db::startTrans();
                try{
                    Db::table('wish_wait_upload_product_variant')->where(['variant_id'=>$sku['variant_id']])->update($update);
                    Db::commit();
                }catch(\Exception $ex){
                    Db::rollback();
                    var_dump($ex->getMessage());
                }
                
            }
            
            $variant = Db::table('wish_wait_upload_product_variant')->where(['product_id'=>$product_id,'lock_variant'=>1])->find();
           
            if($variant)
            {
                $updateP=['lock_update'=>1];  
            }else{
                $updateP=['lock_update'=>0];
            }
            
            Db::startTrans();
            try{
               Db::table('wish_wait_upload_product')->where(['product_id'=>$product_id])->update($updateP);
               Db::commit();
            }catch(\Exception $ex){
                Db::rollback();
                var_dump($ex->getMessage());
            }
            
        }  
    }    
}
