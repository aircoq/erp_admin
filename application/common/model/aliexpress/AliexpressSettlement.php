<?php
namespace app\common\model\aliexpress;

use app\common\cache\Cache;
use think\Model;
use app\index\service\Currency;

class AliexpressSettlement extends Model
{

    // 开启时间字段自动写入
    // protected $autoWriteTimestamp = true;

    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    //保存财务结算表数据
    public function savaSettle()
    {

    }

    /**
     * settlement 表更新和插入
     * @param $order
     *@example $order = [
     *         'account_id'=>'',//Y 账号id
     *         'order_id'=>'',//Y 平台订单id
     *         'transfer_time'=>'',//N 结算(放款)时间
     *         'transfer_amount'=>'',//N 结算(放款)金额
     *         'payment_time'=>'',//N 付款时间
     *         'payment_amount'=>'',//N 付款金额
     *         'refund_time'=>'',//N 退款时间
     *         'refund_amount'=>'',//N 退款金额
     *         'currency_code'=>'',//N 币种
     *         'shipping_time'=>'',//N 仓库发货时间
     *         'shipping_status'=>'',//N 仓库发货状态
     *         'afflicate_fee'=>'',//N 联盟佣金费用
     *         'channel_cost'=>'',//N 交易佣金
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function settleData($order)
    {
        if (!param($order,'order_id')||!param($order,'account_id')) {
            return;
        }
        $settleModel = new AliexpressSettlement();

        $settle = [];
        $settle['account_id'] = $order['account_id'];
        $settle['aliexpress_order_id'] = $order['order_id'];

        $payment_amount = json_decode(param($order,'pay_amount','{}'),true);
        if (isset($payment_amount['currencyCode'])) {
            $settle['currency_code'] = $payment_amount['currencyCode'];
        }
        if (isset($payment_amount['amount'])) {
            $settle['payment_amount']  = $payment_amount['amount'];
        }

        $transfer_amount = json_decode(param($order,'loan_amount','{}'),true);

        if (isset($transfer_amount['amount'])) {
            $settle['transfer_amount'] = $transfer_amount['amount'];
        }
        if (isset($order['transfer_time']) && $order['transfer_time']){
            $settle['transfer_time'] = $order['transfer_time'];
        }
        $refund_amount = json_decode(param($order,'refund_info','{}'),true);
        if (isset($refund_amount['amount'])) {
            $settle['refund_amount'] = $refund_amount['amount'];
        }
        if (isset($order['refund_time_by_report']) && $order['refund_time_by_report']){
            $settle['refund_time_by_report'] = $order['refund_time_by_report'];
        }
        if (isset($order['afflicate_fee']) && $order['afflicate_fee']){
            $settle['afflicate_fee'] = $order['afflicate_fee'];
        }
        if (isset($order['channel_cost']) && $order['channel_cost']){
            $settle['channel_cost'] = $order['channel_cost'];
        }
        if (isset($order['shipping_time']) && $order['shipping_time']>0) {
            $settle['shipping_time'] = $order['shipping_time'];
        }
        if (isset($order['shipping_status']) && $order['shipping_status']) {
            $settle['shipping_status'] = $order['shipping_status'];
        }
        if (isset($order['has_substitute_fee']) && $order['has_substitute_fee']) {
            $settle['has_substitute_fee'] = $order['has_substitute_fee'];
        }
        if (isset($order['has_substitute_fee']) && $order['has_substitute_fee']) {
            $settle['has_substitute_fee'] = $order['has_substitute_fee'];
        }
        $res = $settleModel->field('to_usd_rate,to_cny_rate,transfer_time,transfer_time_by_report,payment_time,refund_time')->where([
            'aliexpress_order_id'=>$order['order_id'],
            'account_id' => $order['account_id']
            ])->find();
  
            //放款时间取报告里的
        $settle['transfer_time_by_report'] = param($order,'transfer_time_by_report',0) >0 ? param($order,'transfer_time_by_report',0) : param($res,'transfer_time_by_report',0);
        $settle['payment_time'] = param($order,'gmt_pay_time',0) > 0 ? param($order,'gmt_pay_time',0) : param($res,'payment_time',0);
        
        //将当前币种的汇率，只在第一次插入，使用付款时间的汇率
        if (param($settle,'currency_code') && $settle['payment_time']>0) {
            //查询对美元汇率
            if(!intval(param($res,'to_usd_rate'))){
                $settle['to_usd_rate'] = Currency::getCurrencyRateByTime($settle['currency_code'],date('Y-m-d',$settle['payment_time']),'USD');
            }
            //查询对人民币汇率
            if(!intval(param($res,'to_cny_rate'))){
                $settle['to_cny_rate'] = Currency::getCurrencyRateByTime($settle['currency_code'],date('Y-m-d',$settle['payment_time']),'CNY');
            }
        }
        
        $settle['refund_time'] = param($order,'refund_time',0)>0 ?param($order,'refund_time',0):param($res,'refund_time',0);
        if ($settle['transfer_time_by_report'] >0 && $settle['payment_time'] > 0 && ($settle['transfer_time_by_report']-$settle['payment_time']>0)) {
            $settle['account_period_week'] = round(($settle['transfer_time_by_report'] - $settle['payment_time']) / 604800);
        }
        if (!$res) {
            $settle['create_time'] = time();
            $settleModel->isUpdate(false)->save($settle);
        }else {
            $settle['update_time'] = time();
            $res->isUpdate(true)->save($settle);
        }
    }

}
