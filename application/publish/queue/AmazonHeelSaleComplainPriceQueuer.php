<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\common\model\amazon\AmazonHeelSaleComplain as AmazonHeelSaleComplainModel;
use app\common\model\amazon\AmazonListing as AmazonListingModel;

class AmazonHeelSaleComplainPriceQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon反跟卖-调价队列';
    }

    public function getDesc():string
    {
        return 'amazon反跟卖-调价队列';
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
                $complainModel = new AmazonHeelSaleComplainModel;
                $result = $complainModel->field('id,price,modify_price_type,modify_price,lowest_price,price,account_id,asin')->where(['id' => $params])->find();

                if($result){

                    $result = $result->toArray();

                    //根据aisn,账号
                    $params = ['account_id' => $result['account_id'], 'asin' => $result['asin']];
                    //提交xml,获取价格最低的在售商品的价格信息
                    $publishHelper = new AmazonPublishHelper();
                    $lowestOffer = $publishHelper->lowestOfferForAsin($params);

                    if(isset($lowestOffer[0]['data']['LowestOfferListings']) && $lowestOffer[0]['data']['LowestOfferListings']){

                        $lowestPrice = $lowestOffer[0]['data']['LowestOfferListings'][0]['Price'];
                        $amount = $lowestPrice['ListingPrice']['Amount'] + $lowestPrice['Shipping']['Amount'];

                        $this->heelSaleUpdatePrice($amount, $result);
                    }
                    return true;
                }

        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }


    /**
     * @param $lowestPrice
     * @param $params
     */
    protected  function heelSaleUpdatePrice($amount, $result)
    {
        //反跟卖产品最低价格小于销售价
       if(isset($amount) && $amount <= $result['price']){

           //百分比调价
           if($result['modify_price_type'] == 1){
                $modify_price = $amount - $amount*$result['modify_price']*0.01;
           }else{
            //金额调价
               $modify_price = $amount - $result['modify_price'];
           }

           //最新低价大于反跟卖录入的低价
           if($result['lowest_price'] <= $modify_price){

               $modify_price = number_format($modify_price, 2);

                //更新跟卖log销售价
               AmazonHeelSaleComplainModel::update(['price' => $modify_price], ['id' => $result['id']]);
           }
       }
    }
}