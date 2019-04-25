<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\common\cache\Cache;
use app\common\model\amazon\AmazonSellerHeelSale as AmazonSellerHeelSaleModel;
use Waimao\AmazonMws\AmazonConfig;


class AmazonSellerNameHeelSaleQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon被跟卖-账号名称抓取';
    }

    public function getDesc():string
    {
        return 'amazon被跟卖-账号名称抓取';
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
        return 40;
    }


    public function execute()
    {
        set_time_limit(0);
        $params = $this->params;
        if (empty($params) || empty($params['id'])) {
            return;
        }

        try {

            $asin = $params['asin'];
            $id  = $params['id'];
            $seller_id = $params['seller_id'];

            $marketplaceId = AmazonConfig::$marketplaceId;
            $marketplaceId = $marketplaceId[$params['site']];

            $amazonWebsiteUrl = AmazonConfig::$AmazonWebsiteUrl;
            $amazonWebsiteUrl = $amazonWebsiteUrl[$params['site']];

            $amazonWebsiteUrl = $amazonWebsiteUrl."/sp?_encoding=UTF8&asin=".$asin."&isAmazonFulfilled=0&isCBA=&marketplaceID={$marketplaceId}&orderID=&seller=$seller_id&tab=&vasStoreID=";

            $contents = file_get_contents($amazonWebsiteUrl); //抓取页面所有内容存入字符串
            preg_match('#<h1 id="sellerName">([^<]*)</h1>#', $contents, $reg);

            if($reg) {
                $seller_name = $reg[1];
                $model = new AmazonSellerHeelSaleModel();

                $model->update(['seller_name' => $seller_name], ['id' => $id]);

            }

            return true;
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }

}