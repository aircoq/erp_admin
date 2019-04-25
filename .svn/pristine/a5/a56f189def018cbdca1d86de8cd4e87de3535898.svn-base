<?php

namespace app\common\model\aliexpress;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;

class AliexpressEvaluate extends ErpModel
{
    use ModelFilter;
    private $filterAccount = [];
    protected $autoWriteTimestamp = true;

    const WAIT_EVALUATE     = 0;
    const PADDING_EVALUATE  = 1;
    const FINSH_EVALUATE    = 2;
    const FAIL_EVALUATE     = 3;
    const EVAULATE_STATUS   = [
        self::WAIT_EVALUATE     => '等待回评',
        self::PADDING_EVALUATE  => '回评中',
        self::FINSH_EVALUATE    => '已回评',
        self::FAIL_EVALUATE     => '回评失败',
    ];
    const SCORE_LABEL = [
        1 => ['id' => 1, 'name' => '差评', 'condition' => ['buyer_evaluation' => ['in', [1, 2]]]],
        2 => ['id' => 2, 'name' => '中评', 'condition' => ['buyer_evaluation' => 3]],
        3 => ['id' => 3, 'name' => '好评', 'condition' => ['buyer_evaluation' => ['in', [4, 5]]]]
    ];

    public static function isExist($orderId)
    {
        if(self::get(['order_id'=>$orderId])){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    /**
     * 根据状态获取数量
     * @param type $status
     * @return type
     */
    public static function getCountByStatus($status)
    {
        return self::where(['status'=>$status])->count();
    }

    public function haveOrder()
    {
        return $this->hasOne(AliexpressOnlineOrder::class, 'order_id','order_id','','LEFT')->field('id,gmt_pay_time,buyer_login_id,pay_amount')->setEagerlyType(0);
    }

    /**
     * 调用AliexpressAccount过滤
     * @param Query $query
     * @param $params
     */
    public function scopeAliexpressAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.aliexpress_account_id', 'in', $this->filterAccount);
        }
    }
}
