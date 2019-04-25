<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class StockRuleSet extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检测规则是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }

    /**
     * 检测规则名称是否存在
     * @param $name
     * @param $id
     * @return bool
     */
    public function isHasName($name, $id = 0)
    {
        if ($id) {
            $where['id'] = ['<>', $id];
        }
        $where['title'] = ['=',$name];
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }

    /** 获取设置规则详情
     * @return \think\model\Relation
     */
    public function item()
    {
        return $this->hasMany(StockRuleSetItem::class, 'rule_id', 'id',
            ['stock_rule_set_item' => 'b', 'stock_rule_set' => 'a'],
            'left')->field('id,rule_id,rule_item_id,param_value')->order('rule_item_id asc');
    }

}