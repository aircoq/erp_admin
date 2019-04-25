<?php

namespace app\publish\queue;

use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\service\CommonQueuer;
use app\publish\service\AmazonPublishConfig;
use think\Db;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\service\UniqueQueuer;

class AmazonTimerUpperPriceQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon-定时上架价格队列';
    }

    public function getDesc():string
    {
        return 'amazon-定时上架价格队列';
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
        return 20;
    }

    public function execute()
    {
        set_time_limit(0);
        $params = $this->params;
        if (empty($id)) {
            return;
        }
        try {

            $xmlhelp = new AmazonXsdToXmlService();
            $xml = $xmlhelp->heelSalePriceXml($params);

            $publishHelper = new AmazonPublishHelper();

            if(empty($publishHelper->publishProductByType($params['account_id'], $xml, '_POST_PRODUCT_PRICING_DATA_'))) {
                (new UniqueQueuer(AmazonTimerUpperPriceQueuer::class))->push($params, 180);
                return;
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}