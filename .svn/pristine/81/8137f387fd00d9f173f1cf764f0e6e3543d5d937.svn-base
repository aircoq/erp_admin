<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonPublishHelper;

class AmazonInfringeEnd extends  SwooleQueueJob{

    public function getName():string
    {
        return 'amazon侵权下架队列';
    }

    public function getDesc():string
    {
        return 'amazon侵权下架队列';
    }

    public function getAuthor():string
    {
        return '冬';
    }

    public function execute()
    {
        try{
            set_time_limit(0);
            $help = new AmazonPublishHelper();
            if (empty($this->params['type'])) {
                $help->infingeEnd($this->params);
            } else if ($this->params['type'] == 1) {
                $help->skuInventory($this->params);
            }
            return true;
        }catch (QueueException $exp){
            throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
        }
    }
}