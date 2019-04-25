<?php
namespace app\common\model;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
/** fba订单模型
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/12
 * Time: 11:50
 */
class FbaOrder extends ErpModel
{
    use ModelFilter;
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function scopeOrder(Query $query, $params)
    {
        $query->where('__TABLE__.channel_account', 'in', $params);
    }
}