<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/13
 * Time: 15:52
 */
class InvoiceRule extends Model
{
    /**
     * 发票规则
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

    /**
     * 查看是否存在
     * @param $id
     * @param $title
     * @return bool
     */
    public function isHasTitle($id,$title)
    {
        $where['id'] = ['<>',$id];
        $where['name'] = ['=',$title];
        $result = $this->where($where)->find();
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** 获取设置规则详情
     * @return \think\model\Relation
     */
    public function item()
    {
        return $this->hasMany(InvoiceRuleSetItem::class, 'rule_id', 'id',
            ['invoice_rule_set_item' => 'b', 'invoice_rule' => 'a'],
            'left')->field('id,rule_id,rule_item_code,param_value')->order('id asc');
    }

    /** 获取模板信息
     * @return \think\model\Relation
     */
    public function template()
    {
        return $this->hasOne(InvoiceTemplate::class, 'id', 'template_id', [], 'left')->field('id,name as template,lang');
    }
}