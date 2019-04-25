<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/13
 * Time: 15:50
 */
class InvoiceResult extends Model
{
    /**
     * 订单记录
     */
    protected function initialize()
    {
        parent::initialize();
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

    /** 获取规则信息
     * @return \think\model\Relation
     */
    public function rule()
    {
        return $this->hasOne(InvoiceRule::class, 'id', 'rule_id', [], 'left')->field('id,name,template_id,custom_area1,custom_area2,custom_area3,custom_area4,custom_area5,tax_rate,date_format,customer_code_rule');
    }
}