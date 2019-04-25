<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Description of EbayRsyncListing
 * @datetime 2017-12-8
 * @author zengsh
 */

class EbayRsyncListing extends Cache
{
    private $setKey = 'ebay:list:itemid:set';

    private $key = 'ebay:list:itemid';

    public function setProductCache($accountId, $itemid, array $data)
    {
        $key = $this->getKeyName($accountId);
        $this->redis->sAdd($this->setKey, $accountId);
        return $this->redis->hSet($key, $itemid, json_encode($data));
    }

    public function getProductCache($accountId, $itemid)
    {
        $key = $this->getKeyName($accountId);
        $cache = $this->redis->hGet($key, $itemid);
        return $cache ? json_decode($cache,true) : [];
    }
    
    private function getKeyName($accountId)
    {
        return $this->key . ':' . $accountId;
    }
}
