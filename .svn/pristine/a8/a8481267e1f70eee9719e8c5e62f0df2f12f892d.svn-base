<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Description of WishRsyncListing
 * 提交编辑了资料的在线listing
 * @datetime 2017-5-6  9:16:57
 * @author joy
 */

class WishRsyncListing extends Cache
{
    private $setKey = 'wish:list:product:set';
    
    private $key = 'wish:list:product';
    
    public function setProductCache($accountId, $product_id, array $data)
    {
        $key = $this->getKeyName($accountId);
        $this->redis->sAdd($this->setKey, $accountId);
        return $this->redis->hSet($key, $product_id, json_encode($data));
    }

    public function getProductCache($accountId, $product_id)
    {
        $key = $this->getKeyName($accountId);
        $cache = $this->redis->hGet($key, $product_id);       
        return $cache ? json_decode($cache,true) : [];
    }
    
    private function getKeyName($accountId)
    {
        return $this->key . ':' . $accountId;
    }
}
