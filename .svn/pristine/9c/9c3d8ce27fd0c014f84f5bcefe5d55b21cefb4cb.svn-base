<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Description of EbayOe
 * @datetime 2017-12-26
 * @author zengsh
 */

class EbayOe extends Cache
{
    private $key = 'ebay:oe';
    private $keyData = 'ebay:oe_number_vechile';

    public function setProductCache($oeUnque,$id)
    {
        return $this->redis->hSet($this->key, $oeUnque,$id);
    }

    public function setProductDataCache($id,$data)
    {
        return $this->redis->hSet($this->keyData, $id,json_encode($data));
    }

    public function getProductCache($oeUnque)
    {
        $cache = $this->redis->hGet($this->key, $oeUnque);
        return $cache ? $cache :0;
    }
    public function getProductDataCache($id)
    {
        $cacheData = $this->redis->hGet($this->key, $id);
        return $cacheData ? json_decode($cacheData,true) :[];
    }
}
