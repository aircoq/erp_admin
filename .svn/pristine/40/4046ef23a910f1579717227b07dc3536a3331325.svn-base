<?php
namespace app\common\cache\driver;
use app\common\cache\Cache;
use app\common\model\WarehouseCargoClass as WarehouseCargoClassModel;

class WarehouseCargoClass extends Cache
{
    const cachePrefix = 'table';
    private $table = self::cachePrefix . ':warehouse_cargo_class:table';
    private $tablePrefix = self::cachePrefix . ':warehouse_cargo_class:';

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
    private function readCaroClass($id = 0)
    {
        $new_list = [];
        $class_list = (new WarehouseCargoClassModel())->field('id, code, name, warehouse_id, width, length, height, max_volume, rate,status')->select();
        foreach ($class_list as $key => $value) {
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
     * 获取分区信息
     * @param int $id
     * @return array|mixed
     */
    public function getWarehouseCargoClass($id = 0)
    {
        $classData = [];
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isExists($key)) {
                $classInfo = $this->redis->hGetAll($key);
            } else {
                $classInfo = $this->readCaroClass($id);
            }
            $classData = $classInfo;
        } else {
            if ($this->isExists($this->table)) {
                $classId = $this->redis->hGetAll($this->table);
                foreach ($classId as $key => $aid) {
                    $key = $this->tablePrefix . $aid;
                    $classData[$aid] = $this->redis->hGetAll($key);
                }
            } else {
                $classData = $this->readCaroClass($id);
            }
        }
        return $classData;
    }

    /**
     * 删除分区缓存信息
     * @param int $id
     */
    public function delCargoClass($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            $this->redis->del($key);
            $this->redis->hDel($this->table, $id);
        } else {
            $classId = $this->redis->hGetAll($this->table);
            foreach ($classId as $key => $aid) {
                $key = $this->tablePrefix . $aid;
                $this->redis->del($key);
            }
            $this->redis->del($this->table);
        }
    }
}

