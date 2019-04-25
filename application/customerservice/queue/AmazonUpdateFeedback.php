<?php
namespace app\customerservice\queue;

use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\customerservice\service\AmazonFeedbackHelp;

class AmazonUpdateFeedback extends  SwooleQueueJob
{
    public function getName():string
    {
	    return 'amazon读取feedback文件并插入到数据表(队列)';
    }

    public function getDesc(): string {
        return 'amazon读取feedback文件并插入到数据表(队列)';
    }

    public function getAuthor(): string {
        return '冬';
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
        try {
            $job = $this->params;
            $result = (new AmazonFeedbackHelp())->updateFeedback($job);
            if($result) {
                unlink($job['path']);
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }
}
