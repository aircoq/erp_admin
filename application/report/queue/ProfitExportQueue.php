<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : ProfitExportQueue.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-08-07
// +----------------------------------------------------------------------

namespace  app\report\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\report\service\ProfitStatement;


class ProfitExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "平台利润导出队列";
    }

    public function getDesc(): string
    {
        return "平台利润导出队列";
    }

    public function getAuthor(): string
    {
        return "LiuLianSen";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $serv = new ProfitStatement();
            $serv->export($this->params);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'hash:report_export',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}