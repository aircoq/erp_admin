<?php

namespace app\goods\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsSkuMapExportService;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/8/10
 * Time: 14:20
 */
class GoodsSkuMapExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "产品映射管理导出队列";
    }

    public function getDesc(): string
    {
        return "产品映射管理导出队列";
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
            $service = new GoodsSkuMapExportService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:goods_sku_map_export', 'error_'.time(), $ex->getMessage());
        }
    }
}