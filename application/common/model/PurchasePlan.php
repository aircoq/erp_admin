<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use think\db\Query;
use erp\ErpModel;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class PurchasePlan extends ErpModel
{
    public const PLAN_TYPE_TEXT = [
        1 => '正常采购',
        2 => '供应商多送',
        3 => '样品',
        4 => '备货计划'
    ];
    use ModelFilter;

    public function scopePurchase(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.purchase_id', 'in', $params);
        }
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检查是否存在
     * @param array $data
     * @return bool
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** 检查代码或者用户名是否有存在了
     * @param $id
     * @param $company_name
     * @return bool
     */
    public function isHas($id, $company_name)
    {
        if (!empty($company_name)) {
            $result = $this->where(['company_name' => $company_name])->where('id', 'NEQ', $id)->select();
            if (!empty($result)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 关联信息
     */
    public function detail()
    {
        return parent::hasMany('PurchasePlanDetail', 'purchase_plan_id','id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'id', 'purchase_plan_id');
    }

}