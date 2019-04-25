<?php
namespace app\index\task;

use app\common\cache\Cache;
use app\index\service\AbsTasker;

class DelCacheKey extends AbsTasker{
    public function getName()
    {
        return "删除指定持久化缓存Key";
    }

    public function getDesc()
    {
        return "删除指定持久化缓存Key";
    }

    public function getCreator()
    {
        return "ZhaiBin";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $keys = ['queue:logs:app\publish\queue\EbayItemOperationQueue', 'queue:logs:app\publish\queue\GoodsPublishMapQueue'];
        $cache = Cache::handler(true);
        $cache->select(1);
        foreach($keys as $key) {
            $cache->del($key);
        }
        
        return true;
    }
}
