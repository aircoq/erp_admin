<?php

namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/11/10
 * Time: 17:29
 */
class AfterSaleRuleSet extends Model
{
    /**
     * 售后规则
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 检测规则是否存在
     * @param int $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }

    /** 获取
     * @param string $field
     * @return mixed
     */
    public function items($field = '*')
    {
        return $this->hasMany(AfterSaleRuleSetItem::class, 'rule_id', 'id',
            ['after_sale_rule_set' => 'a', 'after_sale_rule_set_item' => 'b'],
            'left')->field($field);
    }
}