<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-3-16
 * Time: 下午5:23
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\CommonService;

class WishLocalSellStatus extends SwooleQueueJob
{
    public function getName():string
    {
        return '更新wish在线listing本地销售状态';
    }
    public function getDesc():string
    {
        return '更新wish在线listing本地销售状态';
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
            CommonService::updateListingSellStatus(3,$params);
        }else{
            throw new QueueException("数据为空");
        }
    }
}