<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\GoodsAnalysisService;


class GoodsAnalysisExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "商品销量分析导出队列";
    }

    public function getDesc(): string
    {
        return "商品销量分析导出队列";
    }

    public function getAuthor(): string
    {
        return "laiyongfeng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        try {
            $service = new GoodsAnalysisService();
            $service->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}