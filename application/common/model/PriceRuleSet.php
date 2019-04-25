<?php
namespace app\common\model;

use think\Model;

/** 定价规则缓存
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/18
 * Time: 10:54
 */
class PriceRuleSet extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取设置规则详情
     * @return \think\model\Relation
     */
    public function item()
    {
        return $this->hasMany(PriceRuleSetItem::class, 'rule_id', 'id',
            ['price_rule_set_item' => 'b', 'price_rule_set' => 'a'],
            'left')->field('id,rule_id,rule_item_code,param_value')->order('id asc');
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
}