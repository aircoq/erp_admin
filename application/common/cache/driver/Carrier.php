<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Carrier as CarrierModel;

/**
 * Created by NetBeans.
 * User: ERP
 * Date: 2017/3/13
 * Time: 16:30
 */

class Carrier extends Cache
{

    const cachePrefix = 'table';
    private $table = self::cachePrefix . ':carrier:table';
    private $tablePrefix = self::cachePrefix . ':carrier:';


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
    private function readCarrier($id = 0)
    {
        $new_list = [];

        $carrierModel = new CarrierModel();
        $result = $carrierModel->select();

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
    public function getCarrier($id = 0)
    {
        $carrierData = [];
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isExists($key)) {
                $carrierInfo = $this->redis->hGetAll($key);
            } else {
                $carrierInfo = $this->readCarrier($id);
            }
            $carrierData = $carrierInfo;
        } else {
            if ($this->isExists($this->table)) {
                $carrierId = $this->redis->hGetAll($this->table);
                foreach ($carrierId as $key => $aid) {
                    $key = $this->tablePrefix . $aid;
                    $carrierData[$aid] = $this->redis->hGetAll($key);
                }

            } else {
                $carrierData = $this->readCarrier($id);
            }
        }
        return $carrierData;
    }

    /**
     * 删除缓存信息
     * @param int $id
     */
    public function delCarrier($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            $this->redis->del($key);
            $this->redis->hDel($this->table, $id);
        } else {
            $carrierId = $this->redis->hGetAll($this->table);
            foreach ($carrierId as $key => $aid) {
                $key = $this->tablePrefix . $aid;
                $this->redis->del($key);
            }
            $this->redis->del($this->table);
        }
    }


}