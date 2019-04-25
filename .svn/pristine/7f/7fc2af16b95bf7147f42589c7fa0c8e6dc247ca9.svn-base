<?php
namespace app\common\model;

use think\Model;


/**
 * Class FbsOrder  fbs订单模型
 * Created by linpeng
 * createTime:2018-12-24
 * @package app\common\model
 */
class FbsOrder extends Model
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
        return $this->hasMany(FbsOrderDetail::class,'fbs_order_id','id','left')->field('*');
    }
    public function source()
    {
        return $this->hasMany(FbsOrderSourceDetail::class,'fbs_order_id','id','left')->field('*');
    }
}