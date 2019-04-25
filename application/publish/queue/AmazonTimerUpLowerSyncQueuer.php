<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\service\UniqueQueuer;
use app\publish\service\AmazonHeelSaleLogService;

class AmazonTimerUpLowerSyncQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon-定时上下架同步listing';
    }

    public function getDesc():string
    {
        return 'amazon-定时上下架同步listing';
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

        if (empty($params)) {
            return;
        }
        try {

            (new AmazonHeelSaleLogService)->timerUpLowerSyncListing($params);
            return true;

        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }



}