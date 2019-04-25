<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/19
 * Time: 10:58
 */

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressProduct;

class AliexpressProductCache extends Cache
{
    /**
     * 检测本地产品数据是否最新
     * @param int $accountId
     * @param int $productId
     * @param int $time
     * @return boolean
     */
    public function checkModifiedTime($accountId,$productId,$time)
    {
        if($this->redis->hexists('cache:AliexpressProductCache', $accountId.':'.$productId)){
            $value = $this->redis->hget('cache:AliexpressProductCache', $accountId.':'.$productId);
            if($value==$time){
                return true;
            }
        }
        $model = new AliexpressProduct();
        $result = $model->where(['product_id' => $productId,'account_id'=>$accountId])->field('gmt_modified')->find();
        if(!empty($result)){
            if($result['gmt_modified']==$time){
                return true;
            }
        }
        return false;
    }

    public function setModifiedTime($accountId,$productId,$time)
    {
        $this->redis->hset('cache:AliexpressProductCache', $accountId.':'.$productId,$time);
    }
    public function getProductData($product_id){

        $cache =  $this->redis->hGet("hash:aliexpress:product",$product_id);
        if($cache)
        {
            $cache=json_decode($cache,true);
        }
        return $cache;
    }
    public function setProductData($product_id,$data)
    {
        $this->redis->hSet("hash:aliexpress:product",$product_id,json_encode($data));
    }
}