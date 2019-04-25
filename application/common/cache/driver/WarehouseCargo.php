<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\WarehouseCargo as WarehouseCargoModel;

class WarehouseCargo extends Cache
{

    const cachePrefix = 'table';
    private $tablePrefix = self::cachePrefix . ':warehouse_cargo:';

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


    /** 获取货位信息
     * @param $id
     * @return array|mixed
     */
    private function readCargo($id)
    {
        $WarehouseCargo = (new WarehouseCargoModel())->field('id, code, warehouse_area_type, warehouse_shelf_id, warehouse_area_id, warehouse_id, cargo_class_id, sku_num, wait_sku_num, item_sku, status')->where('id', $id)->find();
        if($WarehouseCargo) {
            $WarehouseCargo = $WarehouseCargo->toArray();
            /*$key = $this->tablePrefix . $WarehouseCargo['id'];
            foreach ($WarehouseCargo as $k => $v) {
                $this->setData($key, $k, $v);
            }*/
            return $WarehouseCargo;
        }
        return array();
    }

    /**
     * 获取货位信息
     * @param int $id
     * @return array|mixed
     */
    public function getWarehouseCargo($id)
    {
       /* $key = $this->tablePrefix . $id;
        if ($this->isExists($key)) {
            $cargoInfo = $this->redis->hGetAll($key);
        } else {*/
            $cargoInfo = $this->readCargo($id);
//        }
        return $cargoInfo;
    }

    /**
     * 删除货位缓存信息
     * @param int $id
     */
    public function delCargo($id)
    {
        $key = $this->tablePrefix . $id;
        $this->redis->del($key);
    }
}


