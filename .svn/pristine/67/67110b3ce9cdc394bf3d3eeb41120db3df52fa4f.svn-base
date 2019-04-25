<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Collector as CollectorModel;

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2018/8/04
 * Time: 10:30
 */

class Collector extends Cache
{

    const cachePrefix = 'table';
    private $table = self::cachePrefix . ':collector:table';
    private $tablePrefix = self::cachePrefix . ':collector:';


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
    private function setData($key, $field, $value)
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
    private function setTable($field, $value)
    {
        if (!$this->isFieldExists($this->table, $field)) {
            $this->redis->hSet($this->table, $field, $value);
        }
    }


    /** 获取物流商信息
     * @param $id
     * @return array|mixed
     */
    private function readCollector($id = 0)
    {
        $new_list = [];

        $collectorModel = new CollectorModel();
        $result = $collectorModel->select();

        foreach ($result as $key => $value) {
            $value = $value->toArray();
            $key = $this->tablePrefix . $value['id'];
            foreach ($value as $k => $v) {
                $this->setData($key, $k, $v);
            }
            $this->setTable($value['id'], $value['id']);
            $new_list[intval($value['id'])] = $value;
        }
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        } else {
            return $new_list;
        }
    }



    /**
     * 获取物流商信息
     * @param int $id
     * @return array
     */
    public function getCollector($id = 0)
    {
        $collectorData = [];
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isExists($key)) {
                $collectorInfo = $this->redis->hGetAll($key);
            } else {
                $collectorInfo = $this->readCollector($id);
            }
            $collectorData = $collectorInfo;
        } else {
            if ($this->isExists($this->table)) {
                $collectorId = $this->redis->hGetAll($this->table);
                foreach ($collectorId as $key => $aid) {
                    $key = $this->tablePrefix . $aid;
                    $collectorData[$aid] = $this->redis->hGetAll($key);
                }
            } else {
                $collectorData = $this->readCollector($id);
            }
        }
        return $collectorData;
    }

    /**
     * 删除缓存信息
     * @param int $id
     */
    public function delCollector($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            $this->redis->del($key);
            $this->redis->hDel($this->table, $id);
        } else {
            $collectorId = $this->redis->hGetAll($this->table);
            foreach ($collectorId as $key => $aid) {
                $key = $this->tablePrefix . $aid;
                $this->redis->del($key);
            }
            $this->redis->del($this->table);
        }
    }


}