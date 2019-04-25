<?php
namespace app\common\model;

use think\Model;

/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/14
 * Time: 17:57
 */
class ShippingMethodDetail extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
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

    //单边长
    public function getVolumeLengthAttr($value)
    {
        return $value/10;
    }

    public function setVolumeLengthAttr($value)
    {
        return floatval($value)*10;
    }
}
