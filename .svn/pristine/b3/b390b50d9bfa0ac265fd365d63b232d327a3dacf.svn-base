<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class OrderLog extends Model
{
    /**
     * 订单
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    public static function lastOrderLog($orderId, $packageId, $status)
    {
        $self  = new self();
        if($orderId){
            $self->where('order_id',$orderId);
        }
        if($packageId){
            $self->where('package_id', $packageId);
        }
        if($status){
            $self->where('process_id', $status);
        }
//        echo "{$orderId}  {$packageId}  {$status}\n";
        $self->order('create_time','ASC');
        return $self->limit(1)->find();
    }

    /** 创建时间获取器
     * @param $value
     * @return int
     */
    public function getCreateTimeAttr($value)
    {
        if(is_numeric($value)){
            return $value;
        }else{
            return strtotime($value);
        }
    }

    /** 更新时间获取器
     * @param $value
     * @return int
     */
    public function getUpdateTimeAttr($value)
    {
        if(is_numeric($value)){
            return $value;
        }else{
            return strtotime($value);
        }
    }
}