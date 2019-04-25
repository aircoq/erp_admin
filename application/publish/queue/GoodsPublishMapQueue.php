<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-3
 * Time: 上午11:47
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsPublishMapService;

class GoodsPublishMapQueue extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }
    public function getName():string
    {
        return '商品刊登状态队列';
    }
    public function getDesc():string
    {
        return '商品刊登状态队列';
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
            if(isset($params['id']) && isset($params['spu']))
            {
                (new GoodsPublishMapService())->saveGoodsData($params);
            }else{
                throw new QueueException("数据格式不合法");
            }
        }else{
            throw new QueueException("数据为空");
        }
    }
}