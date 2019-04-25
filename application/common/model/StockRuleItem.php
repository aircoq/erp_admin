<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class StockRuleItem extends Model
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
            0 => '条件',
        ];
        if ($classified == -1) {
            return $class;
        }
        return isset($class[$classified]) ? $class[$classified] : '';
    }
}