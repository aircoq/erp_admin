<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\OrderLackService;

/**
 * 库存管理_缺货列表导出队列
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/6/14
 * Time: 20:12
 */
class OrderLackExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "库存管理_缺货列表导出队列";
    }

    public function getDesc(): string
    {
        return "库存管理_缺货列表导出队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new OrderLackService();
            $service->export($data, '', 1);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}