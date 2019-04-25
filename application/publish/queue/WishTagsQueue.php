<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-13
 * Time: 下午2:52
 */

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\YixuanpinService;

class WishTagsQueue extends SwooleQueueJob {
    public function getName():string
    {
        return 'wish易选品tags';
    }
    public function getDesc():string
    {
        return 'wish易选品tags';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;
            if($params)
            {
                if(isset($params['results']) && $params['results'])
                {
                    $tags = $params['results'];
                    $query=$params['query'];
                    (new YixuanpinService())->saveTags($tags,$query);
                }
            }
        }catch (QueueException $exp) {
            throw new QueueException($exp->getMessage());
        }
    }

}