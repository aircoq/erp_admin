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

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayFeedbackHelp;
use think\Exception;


class EbayFeedbackLeftQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "ebay回评队列";
    }

    public function getDesc(): string
    {
        return "ebay回评队列";
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
            if (!is_numeric($this->params)) {
                return;
            }
            $service = new EbayFeedbackHelp();
            $service->leaveFeedbackAuto($this->params);

        }catch (Exception $ex){
            throw new Exception($ex->getMessage(). '|'. $ex->getLine(). '|'. $ex->getFile());
        }
    }
}