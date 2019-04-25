<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2019/1/15
 * Time: 14:12
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;
use app\index\service\EbayAccountHealthService;
use think\Exception;

class EbayAccountHealthExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "eBay健康监控数据导出";
    }

    public function getDesc(): string
    {
        return "eBay健康监控数据导出";
    }

    public function getAuthor(): string
    {
        return "wlw2533";
    }

    public function execute()
    {
        try {
            $param = $this->params;
            (new EbayAccountHealthService())->export($param,1);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

    }
}