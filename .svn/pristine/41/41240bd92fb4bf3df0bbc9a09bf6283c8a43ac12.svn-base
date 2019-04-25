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


class PaypalDisputeOperateQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "paypal纠纷-处理队列";
    }

    public function getDesc(): string
    {
        return "paypal纠纷-处理队列";
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
            if (!is_numeric($this->params)) {
                return;
            }
            $service = new PaypalDisputeService();
            $service->operateByRecord($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage(). '|'. $ex->getLine(). '|'. $ex->getFile());
        }
    }
}