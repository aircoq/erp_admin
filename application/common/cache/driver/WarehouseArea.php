<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\WarehouseArea as WarehouseAreaModel;
use app\common\model\WarehouseAreaCategory as WarehouseAreaCategoryModel;

class WarehouseArea extends Cache
{

    const cachePrefix = 'table';
    private $table = self::cachePrefix . ':warehouse_area:table';
    private $tablePrefix = self::cachePrefix . ':warehouse_area:';

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

    /** 获取分区信息
     * @param $id
     * @return array|mixed
     */
    private function readArea($id = 0)
    {
        $areaModel = new WarehouseAreaModel();
        $new_list = [];
        $area_list = $areaModel->field('id, name, code, auto_allocation, warehouse_area_type, warehouse_id, floor_id,status, operator_ids, sort')->order('id asc')->select();

        //分区绑定分类
        $warehouseAreaCategory = new WarehouseAreaCategoryModel();
        $category_info = $warehouseAreaCategory->field('category_id, warehouse_area_id')->select();
        $category_ids = [];
        foreach ($category_info as $item) {
            if (!isset($category_ids[$item->warehouse_area_id])) {
                $category_ids[$item->warehouse_area_id] = [];
            }
            if (!in_array($item->category_id, $category_ids[$item->warehouse_area_id])) {
                array_push($category_ids[$item->warehouse_area_id], $item->category_id);
            }
        }

        foreach ($area_list as $key => $value) {
            $value = $value->toArray();
            $area_category_ids = isset($category_ids[$value['id']]) ? $category_ids[$value['id']] : [];
            $value['category_ids'] = json_encode($area_category_ids, JSON_UNESCAPED_UNICODE);
            $key = $this->tablePrefix . $value['id'];
            $value['operator_ids']  = explode(',', $value['operator_ids']);
            foreach ($value as $k => $v) {
                if ($k == 'operator_ids') { //分区管理员
                    $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                }
                $this->setData($key, $k, $v);
            }
            $this->setTable($value['id'], $value['id']);
            $value['category_ids']  = $area_category_ids;
            $new_list[intval($value['id'])] = $value;
        }
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        } else {
            return $new_list;
        }
    }

    /**
     * 获取分区信息
     * @param int $id
     * @return array|mixed
     */
    public function getWarehouseArea($id = 0)
    {
        $areaData = [];
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isExists($key)) {
                $areaInfo = $this->redis->hGetAll($key);
                $areaInfo['category_ids'] = json_decode($areaInfo['category_ids'], true);
                $areaInfo['operator_ids'] = json_decode($areaInfo['operator_ids'], true);
            } else {
                $areaInfo = $this->readArea($id);
            }
            $areaData = $areaInfo;
        } else {
            if ($this->isExists($this->table)) {
                $areaId = $this->redis->hGetAll($this->table);
                foreach ($areaId as $key => $aid) {
                    $key = $this->tablePrefix . $aid;
                    $areaData[$aid] = $this->redis->hGetAll($key);
                    $areaData[$aid]['category_ids'] = json_decode($areaData[$aid]['category_ids'], true);
                    $areaData[$aid]['operator_ids'] = json_decode($areaData[$aid]['operator_ids'], true);
                }
            } else {
                $areaData = $this->readArea($id);
            }
        }
        return $areaData;
    }

    /**
     * 删除分区缓存信息
     * @param int $id
     */
    public function delArea($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            $this->redis->del($key);
            $this->redis->hDel($this->table, $id);
        } else {
            $areaId = $this->redis->hGetAll($this->table);
            foreach ($areaId as $key => $aid) {
                $key = $this->tablePrefix . $aid;
                $this->redis->del($key);
            }
            $this->redis->del($this->table);
        }
    }

}

