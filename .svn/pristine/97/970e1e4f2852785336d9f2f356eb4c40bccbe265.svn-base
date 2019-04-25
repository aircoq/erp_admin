<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/6
 * Time: 18:05
 */
class PickingPackage extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取订单sku详情
     * @param string $field
     * @return mixed
     */
    public function detail($field = 'package_id,goods_id,sku,sku_id,sku_quantity,sku_price')
    {
        return $this->hasMany(OrderDetail::class, 'package_id', 'package_id')->field($field)->order('sku_quantity asc');
    }

    /** 获取订单包裹信息详情
     * @param string $field
     * @return mixed
     */
    public function package($field = 'id,channel_id,channel_account_id,distribution_time,status,number,create_time,warehouse_id,status,shipping_number,process_code,shipping_id,shipping_name,packing_id,process_code,package_upload_status')
    {
        return $this->hasOne(OrderPackage::class, 'id', 'package_id')->field($field);
    }
}