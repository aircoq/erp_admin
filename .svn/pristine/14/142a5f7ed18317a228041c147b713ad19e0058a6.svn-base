<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/27
 * Time: 16:44
 */

namespace app\common\model\shopee;

use erp\ErpModel;

class ShopeeCancel extends ErpModel
{

    //退货处理状态码
    const STATUS_CODE_TODO = 1;
    const STATUS_CODE_DOING = 2;
    const STATUS_CODE_DONE = 3;
    const STATUS_CODE_TEXT = [
        self::STATUS_CODE_TODO=>'待处理',
        self::STATUS_CODE_DOING=>'处理中',
        self::STATUS_CODE_DONE=>'处理完'
    ];

    const STATUS_IN_CANCEL = 'IN_CANCEL';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_TO_CONFIRM_RECEIVE = 'TO_CONFIRM_RECEIVE';
    const STATUS_INVALID = 'INVALID';
    const STATUS_TO_RETURN = 'TO_RETURN';
    const STATUS_READY_TO_SHIP = 'READY_TO_SHIP';
    const STATUS_RETRY_SHIP = 'RETRY_SHIP';
    const STATUS_SHIPPED = 'SHIPPED';
    const STATUS_UNPAID = 'UNPAID';

    const STATUS_TEXT = [
        self::STATUS_IN_CANCEL => '待取消',
        self::STATUS_CANCELLED => '已取消',
        self::STATUS_COMPLETED => '已完成',
        self::STATUS_TO_CONFIRM_RECEIVE => '确认收货',
        self::STATUS_INVALID => '作废',
        self::STATUS_TO_RETURN => '退货',
        self::STATUS_READY_TO_SHIP => '准备发货',
        self::STATUS_RETRY_SHIP => '再次发货',
        self::STATUS_SHIPPED => '已发货',
        self::STATUS_UNPAID => '未付款'
    ];

    const REASON_BUYER_REQUESTED = 'BUYER_REQUESTED';
    const REASON_SELLER_REQUESTED = 'SELLER_REQUESTED';
    const REASON_TEXT = [
        self::REASON_BUYER_REQUESTED => '买方申请',
        self::REASON_SELLER_REQUESTED => '卖方申请'
    ];

    public function orders()
    {
        return $this->hasOne(ShopeeOrder::Class,'order_sn', 'ordersn');
    }

    public function getStatusCodeTxtAttr($value, $data)
    {
        return self::STATUS_CODE_TEXT[$data['status_code']];
    }

}