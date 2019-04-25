<?php
namespace app\common\model\report;

use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/01/05
 * Time: 9:13
 */
class ReportStatisticByDeeps extends ErpModel
{
    use ModelFilter;

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function scopeInterval(Query $query, $between)
    {
        if(isset($between['begin_time'])){
            $query->where('dateline', '>=', $between['begin_time']);
        }
        if(isset($between['end_time'])){
            $query->where('dateline', '<=', $between['end_time']);
        }
    }

    public function scopeChannel(Query $query, $channel)
    {
        $query->where('channel_id', $channel);
    }

    public function scopeAccount(Query $query, $account)
    {
        $query->where('account_id', $account);
    }

    public function scopeSeller(Query $query, $params)
    {
        $query->where('__TABLE__.user_id', 'in', $params);
    }

}