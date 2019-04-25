<?php
namespace app\common\service;

/** 售后单类型
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/26
 * Time: 14:57
 */
class AfterSaleType
{
    //退款
    const Refund = 1;
    //补发货
    const ReplacementGoods = 2;
    //退货
    const ReturnGoods = 4;
    //退款、补发货
    const RefundAndReplacementGoods = 3;
    //退款、退货
    const RefundAndReturnGoods = 5;
    //补发货、退货
    const ReplacementGoodsAndReturnGoods = 6;
    //退款、补发货、退货
    const RefundAndReplacementGoodsAndReturnGoods = 7;
    //拦截
    const HoldUp = 8;

    #审核状态
    //未提交
    const Uncommitted = 1;
    //已提交
    const Submitted = 2;
    //退回修改
    const ReturnModification = 3;
    //审核通过
    const Approval = 4;

    #退款状态
    //未退款
    const NoRefund = 1;
    //退款失败
    const RefundFailed = 2;
    //退款完成
    const RefundCompleted = 3;
    //退款中
    const Refunding = 4;

    #补发货退货
    //已补发货
    const SupplementaryShipment = 1;
    //已收到退货
    const ReturnedGoods = 2;
    //退货中
    const Returning = 3;

}