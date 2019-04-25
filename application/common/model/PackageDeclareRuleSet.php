<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/9
 * Time: 14:48
 */
class PackageDeclareRuleSet extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 查看是否存在
     * @param $id
     * @return bool
     */
    public function isHas($id)
    {
        $result = $this->where(['id' => $id])->find();
        if (!empty($result)) {
            return true;
        }
        return false;
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

    /** 获取
     * @param string $field
     * @return mixed
     */
    public function items($field = '*')
    {
        return $this->hasMany(PackageDeclareRuleSetItem::class, 'rule_id', 'id',
            ['package_declare_rule_set' => 'a', 'package_declare_rule_set_item' => 'b'],
            'left')->field($field);
    }
}