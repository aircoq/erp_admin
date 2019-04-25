<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Description of publish reponse
 * @datetime 2018-2-5
 * @author zengsh
 */
class EbayListingReponseCache extends Cache
{
    private $key = 'ebay:reponse:data:id';
    static public $reCache = null;
    static public function getInstance()
    {
        if(!self::$reCache) self::$reCache = new self();
        return self::$reCache;
    }

    public function setReponseCache($listingId,$data)
    {
        return $this->redis->setex($this->key.":".$listingId,600,json_encode($data));
    }

    public function getReponseCache($listingId)
    {
        $cache = $this->redis->get($this->key.":".$listingId);
        return $cache ? json_decode($cache,true) :0;
    }
}