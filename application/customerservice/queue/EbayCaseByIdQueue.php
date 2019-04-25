<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayCaseQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayDisputeHelp;
use think\Exception;


class EbayCaseByIdQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "Ebay纠纷-CaseById";
    }

    public function getDesc(): string
    {
        return "Ebay纠纷-CaseById";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            if (empty($this->params['account_id']) || empty($this->params['case_id'])) {
                return;
            }
            $account = Cache::store('EbayAccount')->getTableRecord($this->params['account_id']);
            if (empty($account)) {
                return;
            }
            $service = new EbayDisputeHelp();
            $service->downCaseById($this->params['account_id'], $this->params['case_id']);
        }catch (\Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}