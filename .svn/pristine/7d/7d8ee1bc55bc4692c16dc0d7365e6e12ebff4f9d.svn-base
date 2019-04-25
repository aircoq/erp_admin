<?php
namespace  app\finance\queue;

use app\common\service\SwooleQueueJob;
use think\Exception;
use app\finance\service\WishSettlementService;

/**
 * @author wangwei
 * @date 2018-12-11 19:22:32
 */
class WishSettlementExport extends SwooleQueueJob
{
    public function getName(): string
    {
        return "wish结算报告导出队列";
    }

    public function getDesc(): string
    {
        return "wish结算报告导出队列";
    }

    public function getAuthor(): string
    {
        return "wangwei";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $serv = new WishSettlementService();
            $serv->allExport($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}