<?php
namespace app\common\model\wish;

use erp\ErpModel;

/**
 * @author wangwei
 * @date 2018-11-30 14:53:41
 */
class WishSettlementReportDetail extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    /**
     * @desc 连接wish_transaction_type表
     * @author wangwei
     * @date 2018-12-12 9:36:54
     * @param array $condition
     * @param string $field
     * @param number $pageSize
     * @param number $page
     * @param string $order
     * @param string $group
     * @return number|unknown
     */
    public function getByConditionLeftJoinWTT($condition = [], $field = '*', $pageSize = 0, $page = 1, $order = '', $group = ''){
        $model = $this->alias('wsrd');
        $model->join('wish_transaction_type wtt', 'wsrd.wish_transaction_type_id=wtt.id','left');
        $model->where($condition);
        if($order){
            $model->order($order);
        }
        if($group){
            $model->group($group);
        }
        if($field=='count(*)'){
            if($group){
                return count($model->field('count(*)')->select());
            }else{
                return $model->count();
            }
        }else{
            $model->field($field);
            if($pageSize > 0 && $page > 0){
                $model->page($page, $pageSize);
            }
            return $model->select();
        }
    }
    
}
