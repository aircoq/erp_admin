<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayMessageQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayMessageHelp;
use think\Exception;


class EbaySendMessageQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "ebay发送站内信队列";
    }

    public function getDesc(): string
    {
        return "ebay发送站内信队列";
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
            if (empty($this->params) || !is_numeric($this->params)) {
                return;
            }
            $server = new EbayMessageHelp();
            $server->resendSaveData($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}