<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-21
 * Time: 下午2:34
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressHotWordService;

class AliexpressHotwordsQueue extends SwooleQueueJob
{
    public function getName():string
    {
        return '速卖通热词队列';
    }
    public function getDesc():string
    {
        return '速卖通热词队列';
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
            (new AliexpressHotWordService())->saveManyData($params);
        }
    }
}