<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class ShippingMethod extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /*public function getPeriodTypeAttr($value)
    {
        
        $status = [0=>'无时效',1=>'经济型物流',2=>'标准型物流',3=>'特快型物流'];
        return $status[$value];
    }*/
    
    public function getTypeAttr($value)
    {
        $status = [0=>'无对接',1=>'api对接'];
        if(isset($status[$value])){
            return $status[$value];
        }else{
            return "";
        }
    }
    
    public function getHasTrackingNumberAttr($value)
    {
        $status = [0=>'无',1=>'有'];
        if(isset($status[$value])){
            return $status[$value];
        }else{
            return "";
        }
    }
    
    /**
     * 检查是否存在
     * @return boolean True if the record exists
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public static function getCache($shippingId)
    {
        return static::get($shippingId, TIME_SECS_DAY);
    }

    public function getShippingMethodsByCarrier($carrierId)
    {
        return static::where(['carrier_id'=>$carrierId])->cache(TIME_SECS_DAY)->select();
    }
    
    public function relCarrier()
    {
        return $this->hasOne(Carrier::class,'id','carrier_id',[])->field('id, shortname');
    }

    //计算材积最长宽
    public function getMaxWidthAttr($value)
    {
        return $value/10;
    }

    public function setMaxWidthAttr($value)
    {
        return floatval($value)*10;
    }

    //计算材积最长长
    public function getMaxLengthAttr($value)
    {
        return $value/10;
    }

    public function setMaxLengthAttr($value)
    {
        return floatval($value)*10;
    }

    //计算材积最长高
    public function getMaxHeightAttr($value)
    {
        return $value/10;
    }

    public function setMaxHeightAttr($value)
    {
        return floatval($value)*10;
    }

    //计算材积最长三边长
    public function getMaxPerimeterAttr($value)
    {
        return $value/10;
    }

    public function setMaxPerimeterAttr($value)
    {
        return floatval($value)*10;
    }

}