<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-27
 * Time: 下午3:46
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class PandaoRsyncListing extends Cache
{
    private $setKey = 'pandao:list:product:set';

    private $key = 'pandao:list:product';

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