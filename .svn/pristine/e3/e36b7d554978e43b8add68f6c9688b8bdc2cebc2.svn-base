<?php

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonUpcService;
use think\Exception;

class AmazonUpcPoolQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon刊登-存储UPC';
    }

    public function getDesc():string
    {
        return 'amazon刊登-存储UPC';
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
        return 1;
    }

    public function execute()
    {
        set_time_limit(0);
        $max = $this->params;
        if (empty($max)) {
            $max = 0;
        }

        try {
            $serv = new AmazonUpcService();
            $serv->autoGetUpcToPool($max);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}