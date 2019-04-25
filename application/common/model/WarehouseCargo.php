<?php
namespace app\common\model;

use think\Model;

/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/06
 * Time: 17:18
 */
class WarehouseCargo extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function cargoGoods()
    {
        return $this->hasMany('warehouse_cargo_goods', 'warehouse_cargo_id', 'id')->field(true);
    }
}