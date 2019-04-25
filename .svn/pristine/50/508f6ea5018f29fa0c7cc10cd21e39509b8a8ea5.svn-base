<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

class AmazonReport extends Cache
{

    private $key = 'amazon:fba:sync_stock';


    /*
     *设置amazonFBA库存同步信息
     */
    public function getFbaSyncTime($warehouse_id, $amazon_sku)
    {
        $key = $this->getKeyName($warehouse_id);
        $cache = $this->persistRedis->hGet($key, $amazon_sku);
        if ($cache) {
            return $cache ? json_decode($cache, true) : [];
        }
        return [];

    }

    /*
     *获取amazonFBA库存同步信息
     */
    public function setFbaSyncTime($warehouse_id, $amazon_sku, $data)
    {
        $key = $this->getKeyName($warehouse_id);
        if ($data) {
            return $this->persistRedis->hSet($key, $amazon_sku, json_encode($data));
        }
        return false;
    }

    private function getKeyName($warehouse_id)
    {
        return $this->key . ':' . $warehouse_id;
    }

    public function setShippingTypeWithOrder($channlOrderNumber, $shippingType)
    {
        $key = 'table:amazon:shippingType:';
        if ($channlOrderNumber) {
            $this->redis->hSet($key, $channlOrderNumber, $shippingType);
        }

    }
    public function getShippingTypeWithOrder($channlOrderNumber)
    {
        $shippingType = '';
        $key = 'table:amazon:shippingType:';
        if ($channlOrderNumber && $redisShippingType = $this->redis->hGet($key, $channlOrderNumber)) {
            $shippingType = intval($redisShippingType);
        }
        return $shippingType;
    }
}