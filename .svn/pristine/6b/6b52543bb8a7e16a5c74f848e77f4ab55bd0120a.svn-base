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


class EbayCaseQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "Ebay纠纷-Case";
    }

    public function getDesc(): string
    {
        return "Ebay纠纷-Case";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            if (is_int($this->params)) {
                $account_id = $this->params;
                $down_time = 0;
            } else if (isset($this->params['account_id']) && isset($this->params['down_time'])) {
                $account_id = $this->params['account_id'];
                $down_time = $this->params['down_time'];
            } else {
                return;
            }

            $account = Cache::store('EbayAccount')->getTableRecord($account_id);
            if (empty($account)) {
                return;
            }
            $service = new EbayDisputeHelp();
            $service->downCase($account_id, $down_time);
        }catch (\Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}