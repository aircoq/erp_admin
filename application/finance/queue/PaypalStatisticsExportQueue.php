<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/1/14
 * Time: 17:51
 */

namespace app\finance\queue;


use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\finance\service\PaypalTransactionService;

class PaypalStatisticsExportQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return "paypal统计订单导出队列";
    }

    public function getDesc(): string
    {
        return "paypal统计订单导出队列";
    }

    public function getAuthor(): string
    {
        return 'donghaibo';
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new PaypalTransactionService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}