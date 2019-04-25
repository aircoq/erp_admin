<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class OrderRuleSet extends Model
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
                'name' => '分配发货仓库'
            ],
            1 => [
                'id' => 2,
                'name' => '匹配邮寄方式'
            ],
            2 => [
                'id' => 3,
                'name' => '需人工审核'
            ],
            3 => [
                'id' => 4,
                'name' => '自动添加商品备注'
            ],
            4=>[
                'id' => 5,
                'name' => '自动配货'
            ],
            5=>[
                'id' => 6,
                'name' => '添加商品'
            ]
        ];
        return $type;
    }

    /** 获取设置规则详情
     * @return \think\model\Relation
     */
    public function items()
    {
        return $this->hasMany(OrderRuleSetItem::class, 'rule_id', 'id',
            ['order_rule_set_item' => 'b', 'order_rule_set' => 'a'],
            'left')->field('id,rule_id,rule_item_id,param_value')->order('rule_item_id asc');
    }

}