<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayFeedbackQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayFeedbackHelp;
use think\Exception;


class EbayFeedbackByOrderLineItemId extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载ebay评价队列-orderLineItemId";
    }

    public function getDesc(): string
    {
        return "下载ebay评价队列-feedback_id";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            if (empty($this->params) || empty($this->params['account_id']) || empty($this->params['OrderLineItemID'])) {
                return;
            }
            $service = new EbayFeedbackHelp();
            $service->FeedBackByFeedbackOltId($this->params['account_id'], $this->params['OrderLineItemID']);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}