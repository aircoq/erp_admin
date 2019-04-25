<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-3
 * Time: 下午2:36
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\SkuMapService;
use think\Exception;

class WishSkuMapQueue extends SwooleQueueJob
{
    public function getName():string
    {
        return 'wish平台sku与本地sku关联队列';
    }
    public function getDesc():string
    {
        return 'wish平台sku与本地sku关联队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        try{
            $params = $this->params;
            if($params)
            {
                SkuMapService::wishSkuMap($params);
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}