<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-10-16
 * Time: 下午2:51
 */

namespace app\listing\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\listing\service\JoomSyncListingHelper;

class JoomSyncListingQueue extends  SwooleQueueJob
{
    public function getName():string
    {
        return 'Joom抓取listing列表(队列)';
    }
    public function getDesc():string
    {
        return 'Joom抓取listing列表队列)';
    }
    public function getAuthor():string
    {
        return 'wangqing';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public  function execute()
    {
        try {
            $config = $this->params;
            if(!empty($config)) {
                $help = new JoomSyncListingHelper();
                $help->downListing($config);
            }
        }catch (QueueException $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

}