<?php

namespace app\common\model;

use think\Model;

class WarehouseGoodsForecast extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    //废弃
    const STATUS_DISCARD = 0;
    //草稿
    const STATUS_DRAFT = 1;
    //审核
    const STATUS_AUDIT = 2;
    //可用(旺集)
    const STATUS_AVALILABLE = 3;
    //审核不通过
    const STATUS_NOT_PASS = 4;

    /**
     * @desc 状态转化
     * @param mixed $value
     * @param int $warehouse_type
     * @return int
     */
    public function changeStatus($value, $warehouse_type)
    {
        if(is_numeric($value)){
            return $value;
        }
        $status = self::STATUS_DISCARD;
        switch($value){
            case "X":
                $status = self::STATUS_DISCARD;
                break;
            case "D":
                $status = self::STATUS_DRAFT;
                break;
            case "W";
                $status = self::STATUS_AUDIT;
                break;
            case "S":
                $status = self::STATUS_AVALILABLE;
                break;
            case "R";
                 $status =$warehouse_type == 9 ? self::STATUS_AVALILABLE : self::STATUS_NOT_PASS;
                break;
        }
        return $status;
    }


    /**
     * @desc 根据获取重量（g=>kg）
     */
    public function getWeightAttr($value)
    {
        return $value==0 ?$value:$value/1000;
    }

    /**
     * @desc 设置重量（kg=g）
     */
    public function setWeightAttr($value)
    {
        return $value*1000;
    }

    /**
     * @desc 根据获取宽度（mm=>cm）
     */
    public function getWidthAttr($value)
    {
        return $value==0 ?$value:$value/10;
    }

    /**
     * @desc 设置重量（cm=>mm）
     */
    public function setWidthAttr($value)
    {
        return $value*10;
    }

    /**
     * @desc 根据获取长度（mm=>cm）
     */
    public function getLengthAttr($value)
    {
        return $value==0 ?$value:$value/10;
    }

    /**
     * @desc 设置长度（cm=>mm）
     */
    public function setLengthAttr($value)
    {
        return $value*10;
    }

    /**
     * @desc 根据获取高度（mm=>cm）
     */
    public function getHeightAttr($value)
    {
        return $value==0 ?$value:$value/10;
    }

    /**
     * @desc 设置高度（cm=>mm）
     */
    public function setHeightAttr($value)
    {
        return $value*10;
    }


}
