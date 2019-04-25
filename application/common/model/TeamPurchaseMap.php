<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/03/02
 * Time: 9:13
 */
class TeamPurchaseMap extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
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