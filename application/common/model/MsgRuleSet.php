<?php
namespace app\common\model;

use think\Model;
use app\common\exception\JsonErrorException;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class MsgRuleSet extends Model
{
       
    // 站内信触发条件规则
    static  $TRIGGER_RULE = [
        'E1' => '买家下单之后付款(资金未到账)',
        'E2' => '订单收到买家付款',
        'E3' => '订单分配库存',
        'E4' => '订单标记打印',
        'E5' => '订单执行发货',
        'E6' => '订单同步发货状态成功',
        'E7' => '订单妥投/签收',
        'E8' => 'Invoice生成成功',
        'E9' => '长时间未付款',
        'E10' => '买家提起纠纷后',
        'E11' => '买家发送第一封站内信',
        'E12' => '最晚预计到达时间',
        'E13' => '标记联系退款',
        'E14' => '买家发送第一封邮件',
    ];

    // 评价触发条件规则
    static  $TRIGGER_RULE_FEEDBACK = [
        'F1' => '买家下单',
        'F2' => '买家给我们留差评',
        'F3' => '买家给我们留好评',
        'F4' => '买家收到货物',
    ];

    // 发送邮件规则
    static  $SEND_EMAIL_RULE = [
        1 => '只发送到买家在销售渠道中的邮箱(如eBay邮箱)',
        2 => '优先使用支付系统(如PayPal)邮箱,没有时使用销售渠道邮箱',
        3 => '如果存在则同时发送至支付系统邮箱,不存在则只发送至销售渠道邮箱',
        4 => '只发送到买家站内信',
        5 => '只发送评价',
        6 => '只发送回评'
    ];

    // 发送邮件规则
    static  $PLATFORM = [
        1 => 'Ebay',
        2 => '速卖通',
        3 => '亚马逊',
    ];

        
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    
    /**
     * 查找数据
     * @param number $id
     * @throws JsonErrorException
     */
    public function  find($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if(empty($result)){  //不存在
            throw new JsonErrorException('该站内信规则不存在');
        }
        return $result;
    }
    
    /** 检测规则是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if (empty($result)) {   
            return false;
        }
        return true;
    }

    
    
    /** 获取设置规则详情
     * @return \think\model\Relation
     */
    public function item()
    {
        return $this->hasMany(MsgRuleSetItem::class, 'rule_id', 'id',
            ['msg_rule_set_item' => 'b', 'msg_rule_set' => 'a'],
            'left')->field('id,rule_id,rule_item_id,param_value')->order('rule_item_id asc');
    }
    


}