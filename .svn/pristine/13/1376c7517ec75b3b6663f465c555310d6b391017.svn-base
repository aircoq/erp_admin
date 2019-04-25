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

use think\Exception;
use think\Db;
use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayMessageHelp;


class EbayUpdateTransactionIdQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "Ebay提取站内信的交易ID-停止使用";
    }

    public function getDesc(): string
    {
        return "从下载的html中提取Ebay站内信的交易ID";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }

    public function execute()
    {
        try {
            if (empty($this->params)) {
                return false;
            }
            $ids = $this->params;
            if (is_int($ids)) {
                $ids = [$ids];
            }
            $help = new EbayMessageHelp();
            $help->updateTransactionId($ids);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }

}