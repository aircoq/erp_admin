<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/1/12
 * Time: 17:40
 */

namespace app\finance\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\finance\service\EbaySettlementService;

class EbayStatisticsExportQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return "ebay统计订单导出队列";
    }

    public function getDesc(): string
    {
        return "ebay统计订单导出队列";
    }

    public function getAuthor(): string
    {
        return 'donghaibo';
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new EbaySettlementService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}