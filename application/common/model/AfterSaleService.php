<?php
namespace app\common\model;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/18
 * Time: 10:35
 */
class AfterSaleService extends ErpModel
{
    use ModelFilter;
    /**
     * 订单售后
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

    public function scopeOrderSale(Query $query, $params)
    {
        $query->where('__TABLE__.channel_account', 'in', $params);
    }
}
