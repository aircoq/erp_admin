<?php
namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ProfitStatement;
use app\report\service\Settlement;
use think\Exception;


/**
 * Class SettlementExport
 * Created by linpeng
 * createTime:
 * updateTime: time
 * @package app\report\queue
 */
class SettlementExport extends SwooleQueueJob
{
    public function getName(): string
    {
        return "财务结算数据导出队列";
    }

    public function getDesc(): string
    {
        return "财务结算数据导出队列";
    }

    public function getAuthor(): string
    {
        return "linpeng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $serv = new Settlement();
            $serv->allExport($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}