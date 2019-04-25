<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\WarehouseShelf as WarehouseShelfModel;

class WarehouseShelf extends Cache
{
    const cachePrefix = 'table';
    private $tablePrefix = self::cachePrefix . ':warehouse_shelf:';

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
     * 获取货架信息
     * @param $id
     * @return array|mixed
     */
    private function readShelf($id)
    {
        $warehouseShelf = (new WarehouseShelfModel())->field('id, code, warehouse_area_id, warehouse_id, cargo_class_id, row, column, status, face_aisle, sort')->where('id', $id)->find();;
        if($warehouseShelf){
            $warehouseShelf = $warehouseShelf->toArray();
            $key = $this->tablePrefix . $warehouseShelf['id'];
            foreach ($warehouseShelf as $k => $v) {
                $this->setData($key, $k, $v);
            }
            return $warehouseShelf;
        }
        return array();
    }

    /**
     * 获取货架信息
     * @param int $id
     * @return array|mixed
     */
    public function getWarehouseShelf($id)
    {
        $key = $this->tablePrefix . $id;
        if ($this->isExists($key)) {
            $shelfInfo = $this->redis->hGetAll($key);
        } else {
            $shelfInfo = $this->readShelf($id);
        }
        return $shelfInfo;
    }

    /**
     * 删除货架缓存信息
     * @param int $id
     */
    public function delShelf($id)
    {
        $key = $this->tablePrefix . $id;
        $this->redis->del($key);

    }
    /**
     * 批量删除货架缓存信息
     * @param array $ids
     */
    public function delShelves($ids)
    {
        foreach($ids as $id){
            $this->delShelf($id);
        }
    }
}


