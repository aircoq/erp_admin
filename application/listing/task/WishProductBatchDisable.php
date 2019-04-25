<?php
/**
 * @Date:2017-04-07
 * @time:16:03
 * @author joy
 */

namespace app\listing\task;

use app\common\model\wish\WishWaitUploadProduct;
use service\wish\WishApi;
use app\index\service\AbsTasker;
use app\listing\service\RedisListing;
/**
 * @node wish商品批量下架
 * class 
 */
class WishProductBatchDisable extends AbsTasker {
      public function getName()
    {
        return "wish产品批量下架";
    }

    public function getDesc()
    {
        return "wish产品批量下架";
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
          
        $redis = new RedisListing;
        
        $queues = $redis->myZRangeByScore('wishBatchEnable', strtotime('-1 day'), time());
       
        if(is_array($queues))
        {
            foreach ($queues as $pid)
            {
                self::disable($pid,$redis);
            }            
        }              
    }
    
    private static function disable($pid='')
    {
        $info = WishWaitUploadProduct::get(['product_id'=>$pid],['variants','account']);
        
        if($info->product_id && $info->account->access_token)
        {
            $config['access_token']=$info->account->access_token;
            
            $api  = WishApi::instance($config)->loader('Product');
            
            $data['id'] = $info->product_id;
            
            $res = $api->disableProduct($data);
            
            if($res === true) //下架成功，将pid从redis集合中删除
            {
                 //$redis = Cache::handler(true);
                 $redis->myZRem('wishBatchDisable',$pid);
            }     
        }          
    }
}