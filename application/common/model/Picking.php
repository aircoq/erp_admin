<?php
namespace app\common\model;

use think\Model;

/** 拣货
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/6
 * Time: 17:16
 */
class Picking extends Model
{
    protected $pk = 'id';

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取拣货单详情
     * @param string $field
     * @return mixed
     */
    public function detail($field = '*')
    {
        return $this->hasMany(PickingDetail::class, 'picking_id', 'id')->field($field)->order('sort asc');
    }

    /**
     * 获取拣货单包裹信息
     * @param string $field
     * @return $this
     */
    public function package($field = '*')
    {
        return $this->hasMany(PickingPackage::class,'picking_id','id')->field($field);
    }

    public function shippingLabelNorm()
    {
        return $this->hasOne(ShippingLabelNorm::class, 'id', 'special_type');
    }

    public function subPicking()
    {
        return $this->hasMany(Picking::class, 'pid', 'id');
    }
}