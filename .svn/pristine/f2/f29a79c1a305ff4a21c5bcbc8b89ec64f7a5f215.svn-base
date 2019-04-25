<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/23
 * Time: 11:48
 */
class AfterRedeliverDetail extends Model
{
    /**
     * 补发货品信息
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** sku 信息
     * @return \think\model\Relation
     */
    public function skuInfo()
    {
        return $this->hasOne(GoodsSku::class, 'id', 'sku_id', [], 'left')->field('id,sku,spu_name');
    }
}