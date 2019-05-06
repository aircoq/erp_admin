<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 11:41
 */

namespace app\publish\queue;

use app\common\exception\QueueAfterDoException;
use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonPublishTaskService;


class AmazonExportTask extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'Amazon刊登-每日任务导出';
    }

    public function getDesc(): string
    {
        return 'Amazon刊登-每日任务导出';
    }

    public function getAuthor(): string
    {
        return '冬';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        try{
            $service = new AmazonPublishTaskService();
            $service->allExport($this->params);
        }catch (\Exception $ex){
            throw new QueueAfterDoException($ex->getMessage(), 10);
        }
    }
}