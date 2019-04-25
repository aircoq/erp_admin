<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:31
 */

namespace app\common\model;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use app\common\cache\Cache;
use think\db\Query;

class VirtualOrderApply extends ErpModel
{
    use ModelFilter;

    const STATUS = [
        0 => '已作废',
        1 => '待组长审核',
        2 => '待执行',
        3 => '执行中',
        4 => '已完成',
        5 => '已作废',
    ];
    const REASON = [
        1 => '打造爆款',
        2 => '问题帐号',
        3 => '新店铺',
    ];
    const TYPE = [
        0 => '内部订单',
        1 => '外部订单'
    ];

    const status_reviewed_leader = 1;
    const status_executed = 2;
    const status_execution = 3;
    const status_done = 4;
    const status_dead = 5;

    public function scopeVirtualOrderApply(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.seller_id', 'in', $params);
        }
    }

    public function getReasonTxtAttr($value, $data)
    {
        return self::REASON[$data['reason']];
    }

    public function getOperatorNameAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['operator_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    public function getSellerNameAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['seller_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';

    }

    public function getChannelAttr($value, $data)
    {
        return Cache::store('channel')->getChannelName($data['channel_id']);
    }

    public function getTypeAttr($value)
    {
        return self::TYPE[$value];
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS[$data['status']];
    }


}