<?php
namespace app\common\traits;

use app\common\cache\Cache;
use app\common\model\AfterSaleService;
use app\common\model\OrderDetail;
use app\common\model\AfterServiceReason;
use app\common\service\AfterSaleType;
use app\order\service\PaypalOrderService;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/24
 * Time: 15:20
 */
trait OrderSale
{
    /**
     * 获取退款售后信息
     * @param $info
     * @param $orderInfo
     * @param $sales
     * @param $k
     * @param $remark
     * @param $retired_amount
     * @param $refund_pay_info
     */
    public function getRefundInfo($info, $orderInfo, &$sales, $k, &$remark, &$retired_amount, &$refund_pay_info)
    {
        if ($info['approve_status'] == AfterSaleType::Approval && $info['refund_status'] == AfterSaleType::RefundCompleted) {  //审批通过
            $afterServiceModel = new AfterSaleService();
            //查询当前订单的所有审批退款售后单
            $refund_amount = $afterServiceModel->field(true)->where(['order_id' => $orderInfo['id'], 
                'approve_status' => AfterSaleType::Approval, 'refund_status' => AfterSaleType::RefundCompleted])
                ->sum('refund_amount');
            if ($refund_amount == $orderInfo['pay_fee']) {
                $remark = '该订单已退款';
            } else {
                $remark = '该订单已部分退款';
            }
            $retired_amount += $info['refund_amount'];
            $sales[$k]['retired_amount'] += $info['refund_amount'];
            $sales[$k]['retired_amount'] = sprintf("%.4f",
                $sales[$k]['retired_amount'] * $orderInfo['rate']);  //转换为人民币
            $reason = (new AfterServiceReason())->where(['id' => $info['reason']])->value('remark');
            $payment_account = trim($orderInfo['payment_account']);
            $refund_info = [
                'pay_time' => $info['approve_time'],
                'collection_account' => $orderInfo['collection_account'],
                'payment_account' => $payment_account,
                'pay_code' => '无',
                'pay_name' => '系统标记退款',
                'pay_fee' => $info['refund_amount'],
                'pay_link' => '无',
                'currency_code' => $info['refund_currency'],
                'paypal_trx_id' => $info['paypal_trx_id'],
                'reason' => $reason
            ];
            //ebay退款需要关联paypal交易号
            if($orderInfo['channel_id'] == 1)
            {
                $paypalService = new PaypalOrderService();
                $paypal_refund = $paypalService->getRefundInfo($orderInfo['channel_order_number']);
                $refund_info = array_merge($refund_info,$paypal_refund);
            }
            array_push($refund_pay_info, $refund_info);
        }
    }

    /**
     * 获取售后补发货信息
     * @param $info
     * @param $sales
     * @param $k
     * @param $remark
     * @throws \think\Exception
     */
    public function getReplaceInfo($info,&$sales,$k,&$remark)
    {
        if ($info['approve_status'] == AfterSaleType::Approval && $info['reissue_returns_status'] == AfterSaleType::SupplementaryShipment) {  //审批通过
            $remark = '该订单已补发货';
            //商品成本
            $orderDetailModel = new OrderDetail();
            $deliveryDetail = $orderDetailModel->field('sku_id,sku_quantity')->where(['order_id' => $info['redeliver_order_id']])->select();
            foreach ($deliveryDetail as $d => $dd) {
                $skuInfo = Cache::store('goods')->getSkuInfo($dd['sku_id']);
                if (!empty($skuInfo)) {
                    $sales[$k]['delivery_amount'] += $skuInfo['cost_price'] * $dd['sku_quantity'];
                }
            }
        }
    }

    /**
     * 获取售后补发货信息
     * @param $info
     * @param $remark
     * @throws \think\Exception
     */
    public function getReturnInfo($info,&$remark)
    {
        if ($info['approve_status'] == AfterSaleType::Approval && $info['reissue_returns_status'] == AfterSaleType::ReturnedGoods) {  //审批通过
            $remark = '该订单已退货';
        }
    }
}