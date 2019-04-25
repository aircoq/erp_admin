<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/** 分区缓存
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/3
 * Time: 11:06
 */
class Partition extends Cache
{
    protected $_key = 'hash:checkPartition';

    /** 保存分区缓存
     * @param $model
     * @param $timestamp
     * @param null $field
     * @param array $date
     * @param bool|false $shift
     * @return bool
     */
    public function setPartition($model, $timestamp, $field = null, $date = [], $shift = false)
    {
        $key = $model . '|' . date('Ym', $timestamp);
        if ($this->persistRedis->hexists($this->_key, $key)) {
            return true;
        }
        $bool = time_partition($model, $timestamp, $field, $date, $shift);
        if ($bool) {   //写入缓存
            $this->persistRedis->hset($this->_key, $key, 1);
        }
        return true;
    }

    /** 获取分区缓存
     * @param $model
     * @param $timestamp
     * @return bool
     */
    public function getPartition($model, $timestamp)
    {
        $key = $model . '|' . date('Ym', $timestamp);
        if ($this->persistRedis->hexists($this->_key, $key)) {
            return true;
        }
        return false;
    }

    private function delPartition()
    {

    }
}