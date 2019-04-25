<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\listing\service\AmazonActionLogsHelper;
use app\publish\service\AmazonPublishHelper;

class AmazonPublishResultQueuer extends  SwooleQueueJob
 {

    public function getName(): string {
        return 'amazon抓取Feed结果并保存到数据表(队列)';
    }

    public function getDesc(): string {
        return 'amazon抓取Feed结果并保存到数据表(队列)';
    }

    public function getAuthor(): string {
        return '冬';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public  function execute()
    {
        if (empty($this->params)) {
            return false;
        }
        try{
            $params = $this->params;
            if(empty($params['account_id']) || empty($params['submission_id']))
            {
                return false;
            }

            set_time_limit(0);
            $serv = new AmazonActionLogsHelper();
            $serv->actionLogResult($params['account_id'], $params['submission_id']);
        }catch (QueueException $exp){
            throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
        }
    }
}