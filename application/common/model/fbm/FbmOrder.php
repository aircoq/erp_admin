<?php
namespace app\common\model\fbm;

use think\Model;

/**
 * Class FbmOrder
 * Created by linpeng
 * updateTime: 2019/3/23 14:14
 * @package app\common\model
 */
class FbmOrder extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

//    public function scopeOrder(Query $query, $params)
//    {
//        $query->where('__TABLE__.channel_account', 'in', $params);
//    }
    public function detail()
    {
        return $this->hasMany(FbmOrderDetail::class,'fbm_order_id','id')->field('*');
    }
    public function source()
    {
        return $this->hasMany(FbmOrderSourceDetail::class,'fbm_order_id','id')->field('*');
    }
}