<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/9
 * Time: 15:32
 */
class VirtualRuleItem extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取归类的名称
     * @param int $classified
     * @return mixed
     */
    public static function getClassified($classified = -1)
    {
        $class = [
            0 => '默认设置',
            1 => '物流',
            2 => '金额',
            3 => '货品',
            4 => '买家',
            5 => 'lazada',
            6 => 'wish',
            7 => 'ebay'
        ];
        if ($classified == -1) {
            return $class;
        }
        return $class[$classified];
    }
}