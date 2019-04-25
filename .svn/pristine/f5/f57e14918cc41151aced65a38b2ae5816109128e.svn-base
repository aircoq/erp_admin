<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/12
 * Time: 21:22
 */

namespace app\index\queue;


use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\index\service\EbayAccountHealthService;
use think\Exception;

class EbayAccountHealthQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "eBay健康监控数据获取";
    }

    public function getDesc(): string
    {
        return "eBay健康监控数据获取";
    }

    public function getAuthor(): string
    {
        return "wlw2533";
    }

    public function execute()
    {
        try {
            $id = $this->params;
            if (!is_numeric($id)) {
                throw new Exception('未知参数' . $id);
            }
            $service = new EbayAccountHealthService();
            $service->sendRequest($id);
//            Cache::store('EbayAccount')->ebayLastUpdateTime($id,'health',['last_update_time'=>time()]);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}