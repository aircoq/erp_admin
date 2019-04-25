<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\joom\JoomShop as JoomShopModel;
use app\common\model\joom\JoomProduct as JoomProductModel;
use app\common\model\joom\JoomVariant as JoomVariantModel;
use app\common\traits\CacheTable;

/**
 * 速卖通账号缓存
 * Created by NetBeans.
 * User: Rondaful
 * Date: 2018/1/2
 * Time: 19:43
 */
class JoomListing extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:joom:';
    private $listingSyncTime = 'listing_sync_time';
    private $listintlistkey = 'listing_list';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Aliexpress账号获取listing最后更新时间
     * @param int $accountId
     * @return int
     */
    public function getListingSyncTime($accountId)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if ($this->redis->hexists($key, $accountId)) {
            $arr = $this->redis->hget($key, $accountId);
            return $arr ?? 0;
        }
        return 0;
    }

    /**
     * 设置Aliexpress账号同步listing的时间
     * @param int $account_id
     * @param int $syncTime
     * @return array|mixed
     */
    public function setListingSyncTime($account_id, $syncTime)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if (!empty($syncTime)) {
            $this->redis->hset($key, $account_id, $syncTime);
        }
    }

    /**
     * @param $shop_id
     * @param $product_id
     * @param $data
     * @return bool|int
     */
    public function setProductCache($shop_id, $product_id, $data) {
        $key = $this->getProductKeyName($shop_id);
        if ($data) {
            return $this->redis->hSet($key, $product_id, json_encode($data));
        }

        return false;
    }

    public function setVariantCache($shop_id, $vatiant_id, $data) {
        $key = $this->getVariantKeyName($shop_id);
        if ($data) {
            return $this->redis->hSet($key, $vatiant_id, json_encode($data));
        }

        return false;
    }

    public function getProductCache($shop_id, $product_id) {
        $key = $this->getProductKeyName($shop_id);
        $cache = $this->redis->hGet($key, $product_id);
        if ($cache) {
            return $cache ? json_decode($cache,true) : [];
        } else {
            $info = JoomProductModel::where(['product_id' => $product_id, 'shop_id' => $shop_id])->field('id, update_time')->find();
            return $info ? $info->toArray() : [];
        }
    }

    public function getVariantCache($shop_id, $variant_id) {
        $key = $this->getVariantKeyName($shop_id);
        $cache = $this->redis->hGet($key, $variant_id);
        if ($cache) {
            return $cache ? json_decode($cache,true) : [];
        } else {
            $info = JoomVariantModel::where(['variant_id' => $variant_id])->field('id')->find();
            return $info ? $info->toArray() : [];
        }
    }

    private function getProductKeyName($shop_id)
    {
        return $this->taskPrefix. $this->listintlistkey. ':product:'. $shop_id;
    }

    private function getVariantKeyName($shop_id)
    {
        return $this->taskPrefix. $this->listintlistkey. ':variant:'. $shop_id;
    }
}
