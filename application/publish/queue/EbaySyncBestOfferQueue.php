<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/2/26
 * Time: 18:05
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\publish\service\EbayBestOfferService;

class EbaySyncBestOfferQueue extends SwooleQueueJob
{
    protected $maxFailPushCount = 0;


    public function getName():string
    {
        return 'ebay同步best offer队列';
    }

    public function getDesc():string
    {
        return 'ebay同步best offer队列';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        $param = $this->params;
        (new EbayBestOfferService())->doSync($param);
    }

}