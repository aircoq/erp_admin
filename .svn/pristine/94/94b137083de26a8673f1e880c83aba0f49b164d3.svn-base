<?php


namespace app\common\cache\driver;

use app\common\model\shopee\ShopeeOrder as ModelShopeeOrder;
use app\common\cache\Cache;

class ShopeeOrder extends Cache
{

    private $key = 'table:shopee:order:';
    private $set = 'table:shopee:order:set';
    private $orderSyncTime = 'order_sync_time';

    /**
     * 拿取joom账号最后下载订单更新的时间
     * @param int $account_id
     * @return array
     */
    public function getOrderSyncTime($account_id)
    {
        $key = $this->key . $this->orderSyncTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            $result = json_decode($this->persistRedis->hget($key, $account_id), true);
            return $result ?? [];
        }
        return [];
    }

    /**
     * 设置joom账号最后下载订单更新的时间
     * @param int $account_id
     * @param array $time
     */
    public function setOrderSyncTime($account_id, $time = [])
    {
        $key = $this->key . $this->orderSyncTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, json_encode($time));
        }
    }

    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number joom订单id
     * @param array $data
     * @return array|mixed
     */
    public function orderUpdateTime($accountId, $order_number, $data = [])
    {
        //$key = $this->getOrderKey($accountId);
        if ($data) {
            //$this->redis->hset($key, $order_number, json_encode($data));
            return true;
        }
        //$result = json_decode($this->redis->hget($key, $order_number), true);
        $ModelShopeeOrder = new ModelShopeeOrder();
        $ret = $ModelShopeeOrder->where('order_sn', $order_number)->find();
        return $ret ? $ret->toArray() : [];
    }

    public function delOrderUpdateTime($accountId, $order_number)
    {
        $key = $this->getOrderKey($accountId);
        return $this->redis->hDel($key, $order_number);
    }

    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }

    public function setFbsLog($orderNumber, $message, $accountId)
    {
        $key = 'table:shopee:fbs_log:' . $accountId;
        if ($message && $orderNumber) {
            $this->redis->hset($key, $orderNumber, $message);
        }
    }
}