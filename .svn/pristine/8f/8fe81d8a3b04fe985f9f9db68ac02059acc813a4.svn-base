<?php

namespace  app\report\queue;

use app\common\service\SwooleQueueJob;
use app\report\service\Invoicing;


class InvoicingQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "进销存报表导出";
    }

    public function getDesc(): string
    {
        return "进销存报表导出";
    }

    public function getAuthor(): string
    {
        return "laiyongfeng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            (new Invoicing())->export($data);
        }catch (\Exception $ex){
        }
    }
}