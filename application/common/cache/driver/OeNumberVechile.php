<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Description of OeNumberVechile
 * @datetime 2017-12-26
 * @author zengsh
 */

class OeNumberVechile extends Cache
{
    private $key = 'ebay:OeNumberVechile';

    public function setProductCache($oeUnque,$id)
    {
        return $this->redis->hSet($this->key, $oeUnque,$id);
    }

    public function getProductCache($oeUnque)
    {
        $cache = $this->redis->hGet($this->key, $oeUnque);
        return $cache ? $cache :0;
    }
}
