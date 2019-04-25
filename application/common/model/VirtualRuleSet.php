<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/9
 * Time: 15:32
 */
class VirtualRuleSet extends Model
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

    /** 获取操作类型
     * @return array
     */
    public function getActionType()
    {
        $type = [
            0 => [
                'id' => 1,
                'name' => '自动分配买手'
            ],

        ];
        return $type;
    }

    /** 获取设置规则详情
     * @return \think\model\Relation
     */
    public function items()
    {
        return $this->hasMany(VirtualRuleSetItem::class, 'rule_id', 'id',
            ['order_rule_set_item' => 'b', 'order_rule_set' => 'a'],
            'left')->field('id,rule_id,rule_item_id,param_value')->order('rule_item_id asc');
    }

}