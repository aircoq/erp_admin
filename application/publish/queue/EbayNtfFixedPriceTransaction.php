<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/9/4
 * Time: 13:58
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListingVariation;
use app\common\service\SwooleQueueJob;
use app\publish\helper\ebay\EbayPublish;
use app\common\model\ebay\EbayListing;
use app\publish\service\EbayPackApi;
use org\EbayXml;
use think\Exception;

class EbayNtfFixedPriceTransaction extends SwooleQueueJob
{
    public function getName(): string {
        return 'ebay自动补货';
    }

    public function getDesc(): string {
        return 'ebay自动补货';
    }

    public function getAuthor(): string {
        return 'wlw2533';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $key = $this->params;
            if (!is_string($key)) {
                return false;
            }
            $xml = Cache::store('EbayNotification')->getXml($key);
            Cache::handler()->del($key);//获取后直接删除

            if (empty($xml)) {
                throw new Exception('Ebay通知XML的键:'. $key. ' 不存在，或xml为空');
            }
            $xml = $this->getContent($xml);
            $response = new EbayXml($xml);

            if (!$response || !isset($response->xml) || !isset($response->xml['soapenvEnvelope']['soapenvBody']['GetItemTransactionsResponse'])) {
                return false;
            }
            $result =  $response->xml['soapenvEnvelope']['soapenvBody']['GetItemTransactionsResponse'];
            $item = $result['Item'];
            $itemId = $item['ItemID'];
            $list = EbayListing::get(['item_id'=>$itemId, 'application'=>1]);//必须是erp刊登的
            if (empty($list)) {
                return false;
            }
            $list = $list->toArray();
            if ($list['replen'] == 0) {
                return false;
            }
            /*
             * 1.通知返回的信息中默认Item里面默认不包含Variations,没办法确定多属性现有的数量。所以为了精确补货，收到通知后
             * 对于多属性需要主动发起一次GetItemTransactions请求，IncludeVariations字段设置为true
             * 2.返回的信息里面的Quantity字段指的并不是商品现有数量，对于
             *      单属性商品是指（现有量+已售量-本次购买量），所以单属性商品现有数量=Quantity+本次购买量-已售量，
             *          而补货时要提交的数量就是现有量+本次购买量即 Quantity+2*本次购买量-已售量
             *      多属性商品是指现有量+已售量，所以多属性商品现有数量=Quantity(在Variations容器里)-已售量
             *          而补货时要提交的数量就是现有量+本次购买量即Quantity-已售量+本次购买量
             */
            $transactions = $result['TransactionArray']['Transaction'];
            !isset($transactions[0]) && $transactions = [$transactions];
            $updateSkuQty = [];//需要更新的sku数量

            $varSkuQty = [];
            foreach ($transactions as $transaction) {//通知的信息
                $quantityPurchased = $transaction['QuantityPurchased'];//购买量
                if (isset($transaction['Variation']['SKU'])) {//多属性
                    //主动发起一次GetItemTransactions请求
                    $accountInfo = EbayAccount::where(['id' => $list['account_id']])->find();
                    if (empty($accountInfo)) {
                        return false;
                    }
                    $packApi = new EbayPackApi();
                    $verb = 'GetItemTransactions';
                    $api = $packApi->createApi($accountInfo->toArray(), $verb, $list['site']);
                    $xmlInfo['includeVariations'] = 'True';
                    $xmlInfo['itemID'] = $itemId;
                    $xmlInfo['transactionID'] = $transaction['TransactionID'];
                    $xml = $packApi->createXml($xmlInfo);
                    $response = $api->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
                    if (!isset($response['GetItemTransactionsResponse'])) {
                        return false;
                    }
                    $res = $response['GetItemTransactionsResponse'];
                    if ($res['Ack'] != 'Success') {
                        return false;
                    }
                    $variations = $res['Item']['Variations']['Variation'];
                    !isset($variations[0]) && $variations = [$variations];
                    foreach ($variations as $variation) {
                        $quantity = empty($variation['Quantity']) ? 0 : $variation['Quantity'];
                        $quantitySold = empty($variation['SellingStatus']['QuantitySold']) ? 0 : $variation['SellingStatus']['QuantitySold'];
                        $varSkuQty[$variation['SKU']] = $quantity-$quantitySold;
                    }
                    $updateSkuQty[$transaction['Variation']['SKU']] = $varSkuQty[$transaction['Variation']['SKU']]+$quantityPurchased;
                } else { //单属性
                    $sku = $item['SKU'];
                    $quantitySold = empty($item['SellingStatus']['QuantitySold']) ? 0 : $item['SellingStatus']['QuantitySold'];
                    $quantity = $item['Quantity'] + 2*$quantityPurchased-$quantitySold;
                    $updateSkuQty[$sku] = empty($quantity) ? 5 : $quantity;
                }
            }
//            if (isset($item['Variations'])) {//多属性
//                $variations = $item['Variations']['variation'];
//                !isset($variations[0]) && $variations = [$variations];
//                $skuQty = [];
//                foreach ($variations as $variation) {
//                    $skuQty[$variation['SKU']] = $variation['Quantity'];
//                }
//
//                foreach ($transactions as $transaction) {//交易
//                    $sku = $transaction['Variation']['SKU'];
//                    $quantity = $skuQty[$sku] + $transaction['QuantityPurchased'];
//                    $updateSkuQty[$sku] = empty($quantity) ? 5 : $quantity;
//                }
//            } else {//单属性
//                $sku = $item['SKU'];
//                $quantity = $item['Quantity'] + $transactions['QuantityPurchased'];
//                $updateSkuQty[$sku] = empty($quantity) ? 5 : $quantity;
//            }
////            $sku = isset($result['TransactionArray']['Transaction']['Variation']['SKU']) ? $result['TransactionArray']['Transaction']['Variation']['SKU'] : $item['SKU'];
////            $quantity = $item['Quantity'] + $result['TransactionArray']['Transaction']['QuantityPurchased'];
////            $quantity = empty($quantity) ? 10 : $quantity;
            $this->autoReplenish($list, $updateSkuQty);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function getContent($content)
    {
        $content = str_replace(['soapenv:Envelope', 'soapenv:Body'], ['soapenvEnvelope', 'soapenvBody'], $content);
        return $content;
    }

    private function autoReplenish($list, $skuQty)
    {
        try {
            $updateData['item_id'] = $list['item_id'];
            $updateData['listing_sku'] = 0;
            $updateData['account_id'] = $list['account_id'];
            $updateData['site'] = $list['site'];
            $updateData['cron_time'] = 0;
            $updateData['remark'] = '自动补货';
            $updateData['api_type'] = 1;
            $updateData['create_id'] = 0;

            $newVal = [];
            foreach ($skuQty as $sku => $qty) {
                $newVal[] = [
                    'listing_sku' => $sku,
                    'quantity' => $qty
                ];
            }
            $updateData['new_val'] = $newVal;
            $updateData['old_val'] = [];
            $logId = (new EbayPublish())->writeUpdateLog($updateData);
            (new EbayPublish())->pushQueue(EbayUpdateOnlineListing::class, $logId);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}