<?php
namespace app\common\model\wish;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;

class WishAccountHealthList extends ErpModel
{
    use ModelFilter;

    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function scopeOrder(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.wish_account_id', 'in', $params);
        }
    }

}
