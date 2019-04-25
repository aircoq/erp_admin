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


class GoodsReportSaveQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "产品统计信息回写数据库";
    }

    public function getDesc(): string
    {
        return "产品统计信息回写数据库";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    public function execute()
    {

    }
}