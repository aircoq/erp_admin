<?php

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonPublishResultService;
use think\Exception;

class AmazonPublishProductResultQueuer extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'amazon刊登-获取刊登上传结果';
    }

    public function getDesc(): string
    {
        return 'amazon刊登-抓取刊登相关Feed结果并保存到数据表';
    }

    public function getAuthor(): string
    {
        return '冬';
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 30;
    }

    public function execute()
    {
        set_time_limit(0);
        //这个ID是submissionId表里的自增ID！！！
        $id = $this->params;
        if (empty($id) || !is_numeric($id)) {
            return false;
        }

        $help = new AmazonPublishResultService();
        $result = $help->handelPublishResult($id);

        return $result;
    }
}