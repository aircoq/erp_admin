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
use app\report\service\AccountOperationAnalysisService;


class AccountOperationQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "账户运营分析队列";
    }

    public function getDesc(): string
    {
        return "账户运营分析队列";
    }

    public function getAuthor(): string
    {
        return "ZhouFurong";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 3;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new AccountOperationAnalysisService();
            $service->export($data);
        }catch (\Exception $ex){
	        Cache::handler()->hset('hash:report_export', 'error_'.time(), $ex->getMessage());
        }
    }
}