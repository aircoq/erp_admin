<?php

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonPublishResultService;
use think\Exception;

class AmazonPublishErrorUpdateQueuer extends  SwooleQueueJob {


    public function getName():string
    {
        return 'amazon刊登-出错更新';
    }

    public function getDesc():string
    {
        return 'amazon刊登-出错更新';
    }

    public function getAuthor():string
    {
        return '冬';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        if (empty($this->params['detail_id']) || empty($this->params['type']) || empty($this->params['field'])) {
            return false;
        }
        try {
            $help = new AmazonPublishResultService();
            $help->handlePublishError($this->params['detail_id'], $this->params['type'], $this->params['field']);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}