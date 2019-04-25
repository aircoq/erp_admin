<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayCancelQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\AmazonEmail;


class SendEmailQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "发送邮件队列";
    }

    public function getDesc(): string
    {
        return "发送邮件队列";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            if(empty($this->params) || !is_numeric($this->params)) {
                return;
            }
            $service = new AmazonEmail();
            $service->reSendMail($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:down_cancel','error_'.time(),$ex->getMessage());
        }
    }
}