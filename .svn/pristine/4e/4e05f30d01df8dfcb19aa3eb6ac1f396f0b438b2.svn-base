<?php
namespace app\common\model;

use erp\ErpModel;

/**
 * Created by NetBeans.
 * User: PHILL
 * Date: 2016/11/24
 * Time: 14:16
 */

/**
 * @method field($field)
 *
 */
class WarehouseGoods extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    protected static function init()
    {
    }

    /**
     * @param $warehouseId
     * @param $skuId
     * @param string $field
     * @return $this
     */
    public static function getByWarehouseIdSkuId($warehouseId, $skuId, $field = "*")
    {
        return self::where(['warehouse_id'=>$warehouseId,'sku_id'=>$skuId])->field($field)->find();
    }

    /**
     * 检查是否存在
     * @return boolean True if the record exists
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    
    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id', [], 'left')->field('id, category_id, thumb, name, status');
    }
    
    public function goodsSku()
    {
        return $this->hasOne('GoodsSku', 'id', 'sku_id', [], 'left')->field('id, name, spu_name, sku_attributes,thumb');
    }
}