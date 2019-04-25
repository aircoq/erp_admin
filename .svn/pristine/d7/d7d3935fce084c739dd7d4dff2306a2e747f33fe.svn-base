<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\common\model\amazon\AmazonHeelSaleLog as AmazonHeelSaleLogModel;
use app\common\model\amazon\AmazonListing as AmazonListingModel;

class AmazonHeelSaleUpdatePriceQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon跟卖-自动调价队列';
    }

    public function getDesc():string
    {
        return 'amazon跟卖-自动调价队列';
    }

    public function getAuthor():string
    {
        return 'hao';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }


    public function execute()
    {
        set_time_limit(0);
        $params = $this->params;
        if (empty($params)) {
            return;
        }

        try {
                //提交xml,获取价格最低的在售商品的价格信息
                $publishHelper = new AmazonPublishHelper();
                $lowestOffer = $publishHelper->lowestOfferForAsin($params);

                if(isset($lowestOffer[0]['data']['LowestOfferListings']) && $lowestOffer[0]['data']['LowestOfferListings']) {

                    $lowestPrice = $lowestOffer[0]['data']['LowestOfferListings'][0]['Price'];
                    $amount = $lowestPrice['ListingPrice']['Amount'] + $lowestPrice['Shipping']['Amount'];
                    $this->heelSaleUpdatePrice($amount, $params);

                }
                return true;
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }


    /**
     * @param $lowestPrice
     * @param $params
     */
    protected  function heelSaleUpdatePrice($amount, $params)
    {
        //跟卖产品最低价格小于跟卖价
       if(isset($amount) && $amount <= $params['price']){

           //百分比调价
           if($params['modify_price_type'] == 1){
                $modify_price = $amount - $amount*$params['modify_price']*0.01;
           }else{
            //金额调价
               $modify_price = $amount - $params['modify_price'];
           }


           //最新低价大于等于跟卖录入的低价
           if($params['lowest_price'] <= $modify_price){

               $modify_price = number_format($modify_price, 2);
               $heelSaleLogModel = new AmazonHeelSaleLogModel;

                //更新跟卖log销售价
               $heelSaleLogModel->update(['price' => $modify_price, 'is_sync' => 0], ['id' => $params['id']]);

               //更新亚马逊listing销售价(跟卖)
               $where = [
                   'asin1' => $params['asin'],
                   'seller_sku' => $params['seller_sku'],
                   'seller_type' => 2,
                   'account_id' => $params['account_id'],
               ];

               //$listingModel = new AmazonListingModel;
               //$listingModel->update(['price' => $modify_price], $where);
           }

       }
    }
}