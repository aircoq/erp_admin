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

use app\common\service\SwooleQueueJob;
use app\report\service\StatisticOrder;


class OrderReportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "订单统计信息回写数据库";
    }

    public function getDesc(): string
    {
        return "订单统计信息回写数据库";
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
            (new StatisticOrder())->resetReport1(0,$data['begin_time'],$data['end_time']);
        }catch (\Exception $ex){
        }
    }
}