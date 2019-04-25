<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class OrderSourceDetail extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    /** 来源id转字符串
     * @param $value
     * @return string
     */
    public function getIdAttr($value)
    {
        if(is_numeric($value)){
            $value = $value.'';
        }
        return $value;
    }
}