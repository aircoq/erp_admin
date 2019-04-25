<?php
namespace app\common\model;
use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/03/21
 * Time: 19:48
 */
class OrderPackageDeclare extends Model
{
    /**
     * 订单
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取sku
     * @return $this
     */
    public function skuInfo()
    {
        return $this->hasOne(GoodsSku::class, 'id', 'sku_id')->field('*');
    }
}