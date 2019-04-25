<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class WarehouseShippingMethod extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取运输方式
     * @return \think\model\Relation
     */
    public function method()
    {
        return $this->hasOne('ShippingMethod','id','shipping_method_id',[], 'left')->field('id, carrier_id, shortname, type, code, has_tracking_number,status');
    }
    
}