<?php

namespace app\goods\task;

use app\index\service\AbsTasker;
use app\common\service\CommonQueuer;
use think\Exception;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\goods\queue\GoodsWinitLianQueue;

class GoodsWinitLianSync extends AbsTasker
{

    private $queue = null;

    public function __construct()
    {
        $this->queue = new CommonQueuer(GoodsPushIrobotbox::class);
    }

    public function getCreator()
    {
        return 'Yu';
    }

    public function getDesc()
    {
        return '定期更新万邑链商品列表';
    }

    public function getName()
    {
        return '定期更新万邑链商品列表';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $params = [
            'updateStartDate' => \date('Y-m-d',strtotime('-1 day')),
            'updateEndDate' => \date('Y-m-d'),
        ];
        try {
            $queue = new UniqueQueuer(GoodsWinitLianQueue::class);
            $queue->push($params);
        }catch (Exception $ex){
            return json(['message' => $ex->getMessage()], 400);
        }
    }
}