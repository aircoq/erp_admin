<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Description of EbayOe
 * @datetime 2018-2-2
 * @author zengsh
 */
class EbayCategoryCache extends Cache
{
    private $key = 'ebay:category:path:id';

    public function setCategoryCache($siteCateId,$path)
    {
        return $this->redis->setex($this->key.":".$siteCateId,86400,$path);
    }

    public function getCategoryCache($siteCateId)
    {
        $cache = $this->redis->get($this->key.":".$siteCateId);
        return $cache ? $cache :0;
    }
}