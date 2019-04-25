<?php
namespace app\customerservice\queue;

use app\common\cache\Cache;
use app\common\traits\ConfigCommon;
use app\common\service\SwooleQueueJob;
use callBack\CallBackApi;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2019/1/26
 * Time: 10:24
 */

class DistributionStockInCallBack extends SwooleQueueJob
{
    use ConfigCommon;

    public function getName(): string
    {
        return '退货入库信息通知分销';
    }

    public function getDesc(): string
    {
        return '退货入库信息通知分销';
    }

    public function getAuthor(): string
    {
        return "hecheng";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 1;
    }

    public function execute()
    {
        $backInfo = $this->params;
        $url = 'https://wg.brandslink.com:8443/cms/erpToBrandsLink/erpPostAfterSalesOrder/'.$backInfo['channel_order_number'];
//        $url = 'https://gateway.brandslink.com:50104/cms/erpToBrandsLink/erpPostAfterSalesOrder/'.$backInfo['channel_order_number'];
//        $url = 'https://gateway.brandslink.com:8443/cms/erpToBrandsLink/erpPostAfterSalesOrder/'.$backInfo['channel_order_number'];
        Cache::handler()->hset('hash:distribution_channel_order_number', 'call_back_'.time(),
            $backInfo['channel_order_number']);
        try {
            $result = CallBackApi::instance(['call_back' => $url])->loader('order')->stockInCallBack();
            Cache::handler()->hset('hash:distribution_stock_in_success', 'success_'.time(),
                json_encode($result,JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            Cache::handler()->hset('hash:distribution_stock_in_call_back', 'error_'.time(),
                'Message:' . $e->getMessage() . ',File:' . $e->getFile() . ',Line:' . $e->getLine());
        }
    }
}