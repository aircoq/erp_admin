<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\AreaSalesAnalysis;

/**
 * 区域销量分析
 * Created by PhpStorm.
 * User: lanshushu
 * Date: 2019/10/12
 * Time: 17:23
 */
class AreaSalesAnalysisQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "区域销量分析";
    }

    public function getDesc(): string
    {
        return "区域销量分析";
    }

    public function getAuthor(): string
    {
        return "lanshushu";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new AreaSalesAnalysis();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_area_export', 'error_'.time(), $ex->getMessage());
        }
    }
}