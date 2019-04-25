<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:31
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use think\db\Query;
use erp\ErpModel;




class VirtualOrderRefundApply extends ErpModel
{

    public function scopeOrder(Query $query, $params)
    {
        $query->where('ad.channel_account', 'in', $params);
    }

    public function scopeChannel(Query $query, $params)
    {
        $query->where('a.channel_id', 'in', $params);
    }

    public function scopeVirtualOrderFunctionary(Query $query, $params)
    {
        $query->where('t.functionary_id', 'in', $params);
    }


    //审核状态 0-待审核 1-审核通过 2-审核不通过
    const STATUS = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核不通过',
    ];

    //返款状态 0-未返款 1-已返款 2-返款异常 3-取消返款
    const REFUND_STATUS = [
        0 => '未返款',
        1 => '已返款',
        2 => '返款异常',
        3 => '取消返款',
    ];

    //返款方式 1-paypal 2-微信 3-支付宝
    const REFUND_TYPE = [
        0 => '未选择',
        1 => 'paypal',
        2 => '微信',
        3 => '支付宝',
    ];

    public function getOperatorNameAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['operator_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS[$data['status']];
    }

    public function add($data){
        return $this->insert($data);
    }

}