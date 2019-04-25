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
use app\common\service\SwooleQueueJob;
use app\report\service\ProfitStatement;
use app\report\service\StatisticDeeps;
use app\report\service\StatisticGoods;


class WriteBackOrderGoods extends SwooleQueueJob
{
    public function getName(): string
    {
        return "回写商品统计";
    }

    public function getDesc(): string
    {
        return "回写商品统计";
    }

    public function getAuthor(): string
    {
        return "Phill";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 40;
    }

    public function execute()
    {
        try {
            $goodsService = new StatisticGoods();
            $goodsService->updateReportByStatus($this->params);
        }catch (\Exception $ex){

        }
    }
}