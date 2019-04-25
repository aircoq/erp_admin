<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/23
 * Time: 11:49
 */
class AfterWastageGoods extends Model
{
    /**
     * 退货款补发 问题货品表
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
