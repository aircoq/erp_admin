<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : ProfitExportQueue.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-08-07
// +----------------------------------------------------------------------

namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByGoods;
use app\common\service\SwooleQueueJob;
use app\report\service\ProfitStatement;
use app\report\service\StatisticDeeps;
use app\report\service\StatisticGoods;
use app\report\task\DeepsStatisticReport;
use app\report\task\GoodsStatisticReport;
use app\warehouse\service\PackagePackingReport;
use think\Exception;


class GoodsReportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "产品24天统计信息回写数据库";
    }

    public function getDesc(): string
    {
        return "产品24天统计信息回写数据库";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 3;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            (new StatisticGoods())->resetReport($data['begin_time'],$data['end_time']);
        }catch (\Exception $ex){
        }
    }
}