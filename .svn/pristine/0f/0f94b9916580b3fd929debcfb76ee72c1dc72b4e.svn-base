<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\WarehouseCargoGoods as WarehouseCargoGoodsModel;
use app\warehouse\service\WarehouseArea as WarehouseAreaService;

class WarehouseCargoGoods extends Cache
{
    private $reduceQuantity = 'reduce_quantity';
    private $shiftLog = 'shift_log';

    /**
     * sku扣减库存判断
     * @param int $warehouseId
     * @param int $skuId
     * @param int $quantity
     * @return bool
     */
    public function reduceQuantity($warehouseId, $skuId, $quantity)
    {
        $key = $this->reduceQuantity.':'.$warehouseId.':'.$skuId;
        if($quantity){
            if (!$this->redis->Exists($key)) {
                $warehouseCargoGoods = new WarehouseCargoGoodsModel();
                $where['warehouse_id'] = $warehouseId;
                $where['sku_id'] = $skuId;
                $where['warehouse_area_type'] = WarehouseAreaService::TYPE_PICKING;
                $data = $warehouseCargoGoods->where($where)->order('quantity desc')->find();
                if (empty($data)) {
                    return false;
                }
                $available_quantity = $data['quantity']-$data['hold_quantity'];
                $this->redis->setex ($key, 120, $available_quantity);
            }
            $available_quantity = $this->redis->decrBy($key, $quantity);
            if($available_quantity < 0){ //加回来
                $this->redis->IncrBy($key, $quantity);
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * 删除sku扣减库存
     * @param int $warehouseId
     * @param int $skuId
     * @return bool
     */
    public function delReduceQuantity($warehouseId, $skuId = 0)
    {
        if($skuId){
            $key = $this->reduceQuantity.':'.$warehouseId.':'.$skuId;
            $this->redis->del($key);
        } else {
            $key = $this->reduceQuantity.':'.$warehouseId.'*';
            $keys =$this->redis->keys($key);
            foreach ( $keys as $value) {
                $this->redis->del($value);
            }
        }
    }

    /**
     * 移库日志
     * @param int $sku_id
     * @param array $data
     * @return bool
     */
    public function setShiftLog($warehouse_id, $sku_id, $data)
    {
        $key = $this->shiftLog.':'.$warehouse_id.':'.$sku_id;
        if(!empty($data)) {
            $this->redis->hSet($key, date('Y-m-d H:i:s'),json_encode($data));
        }
    }
}


