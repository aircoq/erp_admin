<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\traits\CacheTable;
use think\Model;
/** 缓存调整
 * Created by PhpStorm.
 * User: thomas
 * Date: 2018/5/17
 * Time: 14:01
 */
class LazadaItem extends Cache
{
    private $taskPrefix = 'task:lazada:';
    private $lastRsyncListingSinceTime = 'listing_last_rsyn_since_time';
    private $attributesNameIndex = 'attribute_name_index';

    use CacheTable;

    public function __construct()
    {
        parent::__construct();
    }
    /** 设置lazada账号获取listing最后更新时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setLazadaLastRsyncListingSinceTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingSinceTime;
        if (!empty($time)) {
            return $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** lazada账号获取listing最后更新时间
     * @param $account_id
     * @return array|mixed
     */
    public function getLazadaLastRsyncListingSinceTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingSinceTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /**
     * 保存一张sku属性键缓存表用于同步product时检索sku属性
     * @param $values 键值一样, 上线后修改为可持久化的
     */
    public function setAttributesNameIndex($field, $values)
    {
        $key = $this->taskPrefix . $this->attributesNameIndex;

        if (!$this->redis->hExists($key, $values)) {
            return $this->redis->hSet($key, $field, $values);
        }
    }

    public function getAllAttributesNameIndex()
    {
        $key = $this->taskPrefix . $this->attributesNameIndex;
        $res = $this->redis->hGetAll($key);
        //后期看是否需要一张表
        return $res ?? [];
    }

    public function getAttributesValue($field)
    {
        $key = $this->taskPrefix . $this->attributesNameIndex;
        $res = $this->redis->hGet($key, $field);
        //后期看是否需要一张表
        return $res ?? false;
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

}