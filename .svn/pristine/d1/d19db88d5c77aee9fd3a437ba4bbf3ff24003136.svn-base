<?php
namespace app\common\model;

use think\Model;

/**
 * Created by NetBean.
 * User: Leslie
 * Date: 2017/02/03
 * Time: 14:03
 */
class WarehouseArea extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 分区绑定分类
     */
    protected function category()
    {
        return $this->hasMany('warehouseAreaCategory', 'warehouse_area_id', 'id');
    }
}
