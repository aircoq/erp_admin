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


class CheckOrderGoods extends SwooleQueueJob
{
    public function getName(): string
    {
        return "查询订单商品统计";
    }

    public function getDesc(): string
    {
        return "查询订单商品统计";
    }

    public function getAuthor(): string
    {
        return "Phill";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        $data = $this->params;
        try {
            $goodsService = new StatisticGoods();
            $goodsService->writeBackOrder($data['begin_time'],$data['end_time']);
        }catch (\Exception $ex){

        }
    }
}