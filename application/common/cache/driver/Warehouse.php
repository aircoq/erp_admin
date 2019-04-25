<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\WarehouseShippingMethod;
use think\Db;
use app\common\model\Warehouse as WarehouseModel;
use app\warehouse\service\Warehouse as WarehouseService;

class Warehouse extends Cache
{
    private $_info_key = 'hash:WarehouseInfo';

    const cachePrefix = 'table';
    private $table = self::cachePrefix . ':warehouse:table';
    private $tablePrefix = self::cachePrefix . ':warehouse:';


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


    /** 获取仓库信息
     * @param $id
     * @return array|mixed
     */
    private function readWarehouse($id = 0)
    {
        $new_list = [];
        $warehouse = WarehouseModel::select();

        foreach ($warehouse as $key => $value) {
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
     * 获取所有仓库
     * @param int $id
     * @return array
     */
    public function getWarehouse($id = 0)
    {
        $warehouseData = [];
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isExists($key)) {
                $warehouseInfo = $this->redis->hGetAll($key);
            } else {
                $warehouseInfo = $this->readWarehouse($id);
            }
            $warehouseData = $warehouseInfo;
        } else {
            if ($this->isExists($this->table)) {
                $warehouseId = $this->redis->hGetAll($this->table);
                foreach ($warehouseId as $key => $aid) {
                    $key = $this->tablePrefix . $aid;
                    $warehouseData[$aid] = $this->redis->hGetAll($key);
                }
            } else {
                $warehouseData = $this->readWarehouse($id);
            }
            $service = new WarehouseService();
            foreach($warehouseData as &$item){
                if(isset($item['id'])){
                    $item['id'] = intval($item['id']);
                }
                $item['sort'] = $service->getSort(param($item, 'type'));
            }
            array_multisort(array_column($warehouseData, 'sort'), SORT_ASC, $warehouseData);
        }
        return $warehouseData;
    }

    /* private function changeIdType(&$warehouseData, &$type)
     {
         foreach($warehouseData as &$item){
             if(isset($item['id'])){
                 $item['id'] = intval($item['id']);
             }
             $type[] = param($item, 'type');
         }
     }*/

    /**
     * 删除缓存信息
     * @param int $id
     */
    public function delWarehouse($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            $this->redis->del($key);
            $this->redis->hDel($this->table, $id);
        } else {
            $warehouseId = $this->redis->hGetAll($this->table);
            foreach ($warehouseId as $key => $aid) {
                $key = $this->tablePrefix . $aid;
                $this->redis->del($key);
            }
            $this->redis->del($this->table);
        }
    }



    /**
     * 获取所有仓库
     * @param number $id
     * @return $newResult ：所有参数列表获取是单个仓库
     */
    /* public function getWarehouse($id = 0)
     {
         if ($this->redis->exists('cache:Warehouse')) {
             if ($id > 0) {
                 $result = json_decode($this->redis->get('cache:Warehouse'),true);
                 return isset($result[$id]) ? $result[$id] : [];
             }
             return json_decode($this->redis->get('cache:Warehouse'), true);
         }
         $result = Db::name('warehouse')->field('id, status, name, type')->select();
         $newResult = [];
         if ($result) {
             foreach ($result as $k => $v) {
                 $newResult[$v['id']] = $v;
             }
             $this->redis->set('cache:Warehouse', json_encode($newResult));
         }
         return $newResult;
     }*/


    /**
     * 仓库数据详情
     * @param int $id
     * @return array
     */
    public function getWarehouseInfo($id = 0)
    {
        if ($this->redis->exists($this->_info_key)) {
            if ($id > 0) {
                $result = json_decode($this->redis->hGet($this->_info_key, $id), true);
                return $result ? $result : [];
            } else {
                $result = [];
                foreach($this->redis->hGetAll($this->_info_key) as $warehouse) {
                    $result[] = json_decode($warehouse);
                }

                return $result;
            }
        }
        $result = WarehouseModel::select();
        foreach($result as $list) {
            $this->redis->hSet($this->_info_key, $list['id'], json_encode($list));
        }

        return $id == 0 ? $result : $this->getWarehouseInfo($id);
    }

    /**
     * 删除仓库详情缓存
     * @return boolean
     */
    public function delWarehouseInfo()
    {
        return $this->redis->del($this->_info_key);
    }

    /**
     * 获取仓库名称根据Id
     * @param int $id
     * @return string
     */
    public function getWarehouseNameById($id)
    {
        $info = $this->getWarehouse($id);
        return isset($info['name']) ? $info['name'] : '';
    }

    /**
     * 获取仓库名称根据Id
     * @param int $id
     * @return string
     */
    public function getWarehouseCodeById($id)
    {
        $info = $this->getWarehouse($id);
        return param($info, 'code');
    }

    /**
     * 获取仓库关联物流
     * @param int $warehouse_id
     * @return array
     */
    public function getShippingMethodByWarehouseId($warehouse_id)
    {
        $key    = 'hash:WarehouseToShippingMethod';
        if ($this->redis->hExists($key, $warehouse_id)) {
            $result = json_decode($this->redis->hGet($key, $warehouse_id),true);
        } else {
            $lists = WarehouseShippingMethod::field('shipping_method_id')->where(['warehouse_id' => $warehouse_id])->select();
            $result = [];
            foreach ($lists as $list) {
                $result[] = $list->shipping_method_id;
            }
            $this->redis->hSet($key, $warehouse_id, json_encode($result));
        }
        return $result;
    }

    /**
     * 删除仓库关联物流缓存
     * @return boolean
     */
    public function delShippingMethodByWarehouseId($warehouse_id)
    {
        $key = 'hash:WarehouseToShippingMethod';
        return $this->redis->hDel($key, $warehouse_id);
    }

    /**
     * @desc 删除物流关系关联仓库缓存
     * @param int $shipping_method_id
     * @return boolean
     */
    public function delShippingMethodByShippingId($shipping_method_id)
    {
        $warehouse_ids = (new WarehouseShippingMethod())
            ->where(['shipping_method_id' => $shipping_method_id])
            ->column('warehouse_id');
        foreach ($warehouse_ids as $warehouse_id) {
            $this->delShippingMethodByWarehouseId($warehouse_id);
        }
    }
}