<?php
namespace app\common\model\aliexpress;

use erp\ErpModel;

/**
 * @author wangwei
 * @date 2019-1-8 10:25:28
 */
class AliexpressSettlementReportDetail extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    /**
     * @desc 连接aliexpress_transaction_type表
     * @author wangwei
     * @date 2019-1-8 10:26:17
     * @param array $condition
     * @param string $field
     * @param number $pageSize
     * @param number $page
     * @param string $order
     * @param string $group
     * @return number|unknown
     */
    public function getByConditionLeftJoinATT($condition = [], $field = '*', $pageSize = 0, $page = 1, $order = '', $group = ''){
        $model = $this->alias('wsrd');
        $model->join('aliexpress_transaction_type att', 'wsrd.aliexpress_transaction_type_id=att.id','left');
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
