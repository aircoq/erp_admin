<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressSettlement;
use app\common\model\Order;
use app\common\model\OrderPackage;
use app\common\model\wish\WishSettlement;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use app\order\service\AmazonSettlementReport;
use app\order\service\EbaySettlementService;
use app\report\service\WishSettlementReportService;
use app\order\service\PackageService;

/**
 * Class SettleRewriteShippingQueue
 * Created by linpeng
 * updateTime: 2018/11/30 11:38
 * @package app\report\queue\
 */
class SettleRewriteShippingQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "货物出仓回写财务发货数据";
    }

    public function getDesc(): string
    {
        return "货物出仓回写财务发货数据";
    }

    public function getAuthor(): string
    {
        return "linpeng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    /**
     * Undocumented function
     * @param $params
     */
    /**
    *  $param =[
    * 'package_number' =>123456789,
    * 'shipping_time' => 987654321
    *];
     **/
    public function execute()
    {
        try {
           $params = $this->params;
            $packageNumber = param($params,'package_number',0);
            $shipping_time = param($params,'shipping_time',0);
            $this->rewriteShipping($packageNumber,$shipping_time);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_settleRewrite:RewriteError',
                'error_'.time(),
                $ex->getMessage());
        }
    }

    public function rewriteShipping($package_number,$shipping_time = 0)
    {
        if (!$package_number) {
            return;
        }
        $PackageService = new PackageService();
        $package_number = $PackageService->analysisNumber($package_number);
        $modelPackage = new OrderPackage();
        $orderId = $modelPackage->field('order_id')->where('number',$package_number)->find();
        if (!$orderId) {
            return;
        }
        $modelOrder = new Order();
        $order = $modelOrder->field('delivery_type,channel_id,channel_account_id,channel_order_number')->where('id',$orderId['order_id'])->find();
        if (!$order) {
            return; 
        }
        $updateData = [
            'account_id' => param($order,'channel_account_id'),
            'order_id' => param($order,'channel_order_number'),
            'shipping_time' => $shipping_time,
            'shipping_status' => param($order,'delivery_type',0)
        ];
        switch (param($order,'channel_id',0)){
            case 0:
                return;
                break;
            case ChannelAccountConst::channel_aliExpress:
                AliexpressSettlement::settleData($updateData);
                break;
            case ChannelAccountConst::channel_amazon:
                $update = [
                    'account_id' => param($order,'channel_account_id'),
                    'order_number' => param($order,'channel_order_number'),
                    'shipping_time' => $shipping_time,
                    'shipping_status' => param($order,'delivery_type',0)
                ];
                 $service = new AmazonSettlementReport();
                 $service->updateSettlement($update);
                break;
            case ChannelAccountConst::channel_wish:
                $service = new WishSettlementReportService();
                $service->settleData($updateData);
                break;
            case ChannelAccountConst::channel_ebay:
                $service = new EbaySettlementService();
                $service->updateSettlement($updateData);
                break;
            default:
                return;
        }
    }
}