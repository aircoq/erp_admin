<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/10/16
 * Time: 10:50
 */

namespace app\goods\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\goods\service\GoodsHelp;

class WmsGoodsQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return '商品变化推送到管易';
    }

    public function getDesc(): string
    {
        return '商品变化推送到管易';
    }

    public function getAuthor(): string
    {
        return 'StarZhan';
    }

    public function execute()
    {
        $params = $this->params;
        try {
            $GoodsHelp = new GoodsHelp();
            if(empty($params['goods_id'])){
                throw new Exception('goods_id不存在');
            }
            $GoodsHelp->createGuanyiData($params['goods_id']);
        } catch (Exception $exception) {
            throw new QueueException($exception->getMessage());
        }
    }
}