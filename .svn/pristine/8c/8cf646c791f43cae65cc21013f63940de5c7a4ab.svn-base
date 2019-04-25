<?php
namespace app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\FirstOrderSkuListExportService;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 10:27
 */

class FirstOrderSkuListExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "首次出单SKU列表导出队列";
    }

    public function getDesc(): string
    {
        return "首次出单SKU列表导出队列";
    }

    public function getAuthor(): string
    {
        return "hecheng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new FirstOrderSkuListExportService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}