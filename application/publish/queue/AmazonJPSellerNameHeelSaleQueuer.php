<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonHeelSaleLogService;


class AmazonJPSellerNameHeelSaleQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon-JP被跟卖-账号名称抓取';
    }

    public function getDesc():string
    {
        return 'amazon-JP被跟卖-账号名称抓取';
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
        if (empty($params) || empty($params['id'])) {
            return;
        }

        try {

            (new AmazonHeelSaleLogService())->sellerNames($params);

            return true;
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }

}