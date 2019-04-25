<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;

class AmazonComplainPriceQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon反跟卖-更新价格队列';
    }

    public function getDesc():string
    {
        return 'amazon反跟卖-更新价格队列';
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
            //提交数据
            $xmlhelp = new AmazonXsdToXmlService();
            $xml = $xmlhelp->heelSalePriceXml($params);

            $account = $xmlhelp->getPublishAccount();

            //以下开始上传XML，并保存提交结果ID；
            $publishHelper = new AmazonPublishHelper();
            $submissionId = $publishHelper->publishProductByType($account['id'], $xml, '_POST_PRODUCT_PRICING_DATA_');

            //没有获取submissionId，放入重新放到亚马逊跟卖队列；在task里面拿出来进行刊登
            if (empty($submissionId)) {
                (new UniqueQueuer(AmazonHeelSalePriceQueuer::class))->push($params);
                return;
            }

            return true;
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}