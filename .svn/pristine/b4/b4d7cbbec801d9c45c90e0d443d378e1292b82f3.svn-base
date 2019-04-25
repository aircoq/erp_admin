<?php

namespace app\common\model\report;

use app\common\cache\Cache;
use think\db\Query;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/08/24
 * Time: 15:24
 */
class ReportStatisticByMessage extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function scopeInterval(Query $query, $between)
    {
        if (isset($between['begin_time'])) {
            $query->where('dateline', '>=', $between['begin_time']);
        }
        if (isset($between['end_time'])) {
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

    public function getChannelAttr($value, $data)
    {
        return Cache::store('channel')->getChannelName($data['channel_id']);
    }

    public function getCustomerAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['customer_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    public function getDatelineAttr($value, $data)
    {
        $date = date('Y-m-d', $data['start_time']) .'->'.date('Y-m-d', $data['end_time']);
        return $date;
    }
}