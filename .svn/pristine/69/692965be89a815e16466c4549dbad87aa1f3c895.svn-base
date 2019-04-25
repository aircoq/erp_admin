<?php
namespace app\common\traits;

use app\common\service\AfterSaleType;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/24
 * Time: 10:16
 */
trait AfterSale
{
    /**
     * 获取售后类型
     * @param array $type
     * @return int
     */
    public function setAfterType(array $type)
    {
        $typeNum = 0;
        foreach ($type as $k => $v) {
            $typeNum +=$v;
        }
        return $typeNum;
    }

    /**
     * 获取退款进度状态
     * @param $v
     * @param $refund
     * @param $approve
     */
    public function getRefundStatus(&$v, $refund, $approve)
    {
        if ($v['approve_status'] == AfterSaleType::Approval) {
            $v['refund'] = !empty($v['refund_status']) ? $refund[$v['refund_status']] : $approve[4];
        } else {
            $v['refund'] = !empty($v['approve_status']) ? $approve[$v['approve_status']] : '--';
        }
    }

    /** 获取补发货进度状态
     * @param $v
     * @param $reissue
     * @param $approve
     */
    public function getRedeliverStatus(&$v, $reissue, $approve)
    {
        if ($v['approve_status'] == AfterSaleType::Approval) {
            $v['redeliver'] = !empty($v['reissue_returns_status']) ? $reissue[$v['reissue_returns_status']] : $approve[4];
        } else {
            $v['redeliver'] = !empty($v['approve_status']) ? $approve[$v['approve_status']] : '--';
        }
    }

    /**
     * 获取退货进度状态
     * @param $v
     * @param $reissue
     * @param $approve
     */
    public function getReturnStatus(&$v, $reissue, $approve)
    {
        if ($v['approve_status'] == AfterSaleType::Approval) {
            $v['return'] = !empty($v['reissue_returns_status']) ? $reissue[$v['reissue_returns_status']] : $approve[4];
        } else {
            $v['return'] = !empty($v['approve_status']) ? $approve[$v['approve_status']] : '--';
        }
    }

}