<?php
namespace app\common\cache\driver;
use app\common\cache\Cache;
use app\common\model\WarehouseGoods;
use app\common\service\Encryption;

class Delivery extends Cache
{
    /**
     * 商品库存数
     */
    public function quantityNum($warehouseId = 0, $skuId = 0)
    {
        $num = 0;
        $key = Encryption::keyByWarehouseIdSkuId($warehouseId, $skuId);
//        if (self::$persistRedis->hExists('cache:GoodsVariableNum', $key)) {
//            $num = self::$persistRedis->hGet('cache:GoodsVariableNum', $key);
//        }
//        if (intval($num)) {
//            return $num;
//        }
        $wareHouseModel = new WarehouseGoods();
        $wareHouse = $wareHouseModel->field('available_quantity, usable_quantity')->where('warehouse_id', $warehouseId)->where('sku_id', $skuId)->find();
        
        if (isset($wareHouse['usable_quantity']) && $wareHouse['usable_quantity'] > 0) {
            $num = $wareHouse['usable_quantity'];
        } else {
            $num = isset($wareHouse['available_quantity']) ? $wareHouse['available_quantity'] : 0;
        }
        
        self::$persistRedis->hSet('cache:GoodsVariableNum', $key, $num);
        return $num;
     
    }
    
    /**
     *  更新商品库存可用数
     * @param number $goodsId
     */
    public function updateAvailableQuantity($warehouseId = 0, $skuId = 0, $quantity = 0)
    {
        try {
            $key = Encryption::keyByWarehouseIdSkuId($warehouseId, $skuId);
            self::$persistRedis->hSet('cache:GoodsVariableNum', $key, $quantity);
            return true;
        } catch (Exception $e) {
            return false;
        }
        
    }
        
    /**
     * 删除缓存中的商品
     * @param number $goodsId
     * @return boolean
     */
    public function delGoods($warehouseId = 0, $skuId = 0)
    {
        $key = Encryption::keyByWarehouseIdSkuId($warehouseId, $skuId);
        if (self::$persistRedis->hExists('cache:GoodsVariableNum', $key)) {
            if (self::$persistRedis->hDel('cache:GoodsVariableNum', $key)) {
                return true;
            }
        }
        return false;
    }
}