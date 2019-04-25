<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-28
 * Time: 下午5:56
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\model\shopee\ShopeeProduct;
use app\common\service\SwooleQueueJob;
use app\publish\helper\shopee\ShopeeHelper;
use app\publish\service\ShopeeApiService;
use think\Exception;

class LazadaUpdateListingQueue extends SwooleQueueJob
{
    const PRIORITY_HEIGHT = 10;

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function getName(): string
    {
        return 'lazada更新队列';
    }

    public function getDesc(): string
    {
        return 'lazada更新队列';
    }

    public function getAuthor(): string
    {
        return 'thomas';
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $id = $this->params;
            $res = (new ShopeeHelper())->addItem($id);
            if ($res !== true) {
                throw new Exception($res);
            }
        }catch (Exception $exp) {
            throw new QueueException($exp->getMessage());
        }
    }
}