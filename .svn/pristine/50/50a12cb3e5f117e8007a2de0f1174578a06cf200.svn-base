<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\wish\WishPlatformShippableCountries;
use think\Db;

class Wish extends Cache
{
    const cachePrefix = 'table';
    private $tablePrefix = self::cachePrefix . ':wish_order:';
    private $shippablePrefix = self::cachePrefix . ':wish_shippable';

    /** 获取国家缩写
     * @param string $name
     * @return mixed|string
     */
    public function getCountryCode($name)
    {
        if ($this->redis->hExists($this->shippablePrefix, $name)) {
            $result = $this->redis->hGet($this->shippablePrefix, $name);
            return $result;
        }
        $model = new WishPlatformShippableCountries();
        $result = $model->field('abbreviations, full_name')->where(['full_name' => $name])->find();
        if (!empty($result)) {
            $this->redis->hSet($this->shippablePrefix, $result['full_name'], $result['abbreviations']);
        }
        return $result['abbreviations'] ?? '';
    }

    /**
     * 判断key是否存在
     * @param $key
     * @return bool
     */
    private function isExists($key)
    {
        if ($this->redis->exists($key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断域是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    private function isFieldExists($key, $field)
    {
        if ($this->redis->hExists($key, $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置值
     * @param $key
     * @param $field
     * @param $value
     */
    public function setData($key, $field, $value)
    {
        if (!$this->isFieldExists($key, $field)) {
            $this->redis->hSet($key, $field, $value);
        }
    }

    /**
     * 记录表一共有多少条记录
     * @param $field
     * @param $value
     */
    public function setTable($field, $value)
    {
        if (!$this->isFieldExists($this->table, $field)) {
            $this->redis->hSet($this->table, $field, $value);
        }
    }

    /**
     * 删除值
     * @param $key
     * @param string $field
     */
    public function delData($key, $field = '')
    {
        if (!empty($field)) {
            $this->redis->del($key, $field);
        } else {
            $this->redis->del($key);
        }
    }

    /**
     * 记录订单信息
     * @param $order_id
     * @param $account_id
     * @param $update_time
     */
    public function recordOrder($order_id, $account_id, $update_time)
    {
        $key = $this->tablePrefix . $account_id;
        $this->setData($key, $order_id, $update_time);
    }

    /**
     * 删除单一记录
     * @param $order_id
     * @param $account_id
     */
    public function clearOrder($order_id, $account_id)
    {
        $key = $this->tablePrefix . $account_id;
        $this->delData($key, $order_id);
    }

    /**
     * 检查订单是否存在
     * @param $order_id
     * @param $account_id
     * @return bool
     */
    public function orderIsExist($order_id, $account_id)
    {
        return $this->isFieldExists($this->tablePrefix . $account_id, $order_id);
    }

    /**
     * 获取订单最后更新时间
     * @param $order_id
     * @param $account_id
     * @return int|string
     */
    public function getOrderUpdateTime($order_id, $account_id)
    {
        $time = 0;
        $key = $this->tablePrefix . $account_id;
        if (!$this->isFieldExists($key, $order_id)) {
            $time = $this->redis->hGet($key, $order_id);
        }
        return $time;
    }

    /**
     * 删除过期订单记录
     */
    public function delExpire()
    {
        $now = time();
        $accountInfo = Cache::store('wishAccount')->getAccount();
        foreach ($accountInfo as $key => $value) {
            $orderList = $this->redis->hGetAll($this->tablePrefix . $key);
            foreach ($orderList as $k => $v) {
                $expire = (3600 * 24 * 2) + intval($v);
                if ($expire < $now) {
                    $this->clearOrder($k,$key);
                }
            }
        }
    }
}