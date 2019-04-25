<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressProduct as AliexpressProductModel;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/19
 * Time: 10:58
 */
class AliexpressRsyncListing extends Cache
{

    private $setKey = 'aliexpress:listing:set';
    
    private $key = 'aliexpress:listing';

    /***
     * 获取账号商品修改结束时间
     * @param $accountId
     * @return string
     */
    public function getGmtModifiedEndTime($accountId,$status){
        $cache = $this->redis->hGet("aliexpress:listing:gmt_modified_end:{$status}", $accountId);
        return $cache;
    }

    /***
     * 设置账号商品修改结束时间
     * @param $accountId
     * @param $time
     */
    public function setGmtModifiedEndTime($accountId,$status,$time){
        $this->redis->hSet("aliexpress:listing:gmt_modified_end:{$status}", $accountId,$time);
    }
    /***
     * 获取账号商品修改开始时间
     * @param $accountId
     * @param $time
     */
    public function getGmtModifiedStartTime($accountId,$status){
        $cache = $this->redis->hGet("aliexpress:listing:gmt_modified_start:{$status}", $accountId);
        return $cache;
    }
    /***
     * 设置账号商品修改开始时间
     * @param $accountId
     * @param $time
     */
    public function setGmtModifiedStartTime($accountId,$status,$time){
        $this->redis->hSet("aliexpress:listing:gmt_modified_start:{$status}", $accountId,$time);
    }
    /**
     * 设置缓存信息
     * @param type $accountId
     * @param type $product_id
     * @param array $data ['id' => 1, 'gmt_modified' => 1515424154155]
     * @return boolean
     */
    public function setProductCache($accountId, $product_id, array $data)
    {

        return true;
        /*$key = $this->getKeyName($accountId);
        $this->redis->sAdd($this->setKey, $accountId);
        if ($data) {
            return $this->redis->hSet($key, $product_id, json_encode($data));
        }
        
        return false;*/
    }

    /**
     * 获取listing缓存信息
     * @param int $accountId
     * @param string $productId
     * @return array ['id' => 1, 'gmt_modified' => 1515424154155]
     */
    public function getProductCache($accountId, $productId)
    {
        // $key = $this->getKeyName($accountId);
        /*$cache = $this->redis->hGet($key, $productId);
        if ($cache) {
            return $cache ? json_decode($cache,true) : [];
        }*/
        $info = AliexpressProductModel::where(['product_id' => $productId, 'account_id' => $accountId])->field('id, gmt_modified')->find();
        return $info ? $info->toArray() : [];
    }
    
    private function getKeyName($accountId)
    {
        return $this->key . ':' . $accountId;
    }

}
