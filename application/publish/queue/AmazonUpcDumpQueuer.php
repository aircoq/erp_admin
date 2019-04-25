<?php

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonUpcService;
use think\Exception;

class AmazonUpcDumpQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon-已使用的UPC备份';
    }

    public function getDesc():string
    {
        return 'amazon-已使用的UPC备份';
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
        if (!is_array($this->params)) {
            return;
        }
        if (count($this->params) != 2) {
            return;
        }

        try {
            $serv = new AmazonUpcService();
            $serv->upcToDump($this->params[0], $this->params[1]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}