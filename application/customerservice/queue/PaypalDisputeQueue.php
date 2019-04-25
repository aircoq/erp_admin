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
use app\customerservice\service\PaypalDisputeService;
use think\Exception;


class PaypalDisputeQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载paypal纠纷";
    }

    public function getDesc(): string
    {
        return "下载paypal纠纷";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            if (is_int($this->params)) {
                $account_id = $this->params;
                $down_time = 0;
            } else {
                $account_id = $this->params['account_id'];
                $down_time = $this->params['down_time'];
            }
            $service = new PaypalDisputeService();
            $service->downLoadDispute($account_id, $down_time);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage(). '|'. $ex->getLine(). '|'. $ex->getFile());
        }
    }
}