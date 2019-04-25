<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-3
 * Time: 下午2:51
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\publish\service\SkuMapService;

class AliexpressSkuMapQueue extends SwooleQueueJob
{
    public function getName():string
    {
        return '速卖通平台sku与本地sku关联队列';
    }
    public function getDesc():string
    {
        return '速卖通平台sku与本地sku关联队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        $params = $this->params;
        if($params)
        {
            SkuMapService::AliexpressSkuMap($params);
        }else{
            throw new QueueException("数据为空");
        }
    }
}