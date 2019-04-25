<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayFeedbackQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\model\paypal\PaypalAccount;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\PaypalDisputeService;
use think\Exception;


class PaypalDisputeByIdQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载paypal纠纷-根据纠纷ID";
    }

    public function getDesc(): string
    {
        return "下载paypal纠纷-根据纠纷ID";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $params = $this->params;
            if (empty($params['account']) || empty($params['dispute_id'])) {
                return false;
            }

            $account_id = $params['account'];
            if (!is_numeric($account_id)) {
                $account_id = PaypalAccount::where(['account_name' => $params['account']])->value('id');
                if (empty($account_id)) {
                    throw new Exception('未找到paypal帐号:'. $params['account']);
                }
            }

            $dispute_ids = explode(',', $params['dispute_id']);
            $service = new PaypalDisputeService();
            $service->downLoadDisputeDetail($account_id, $dispute_ids);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage(). '|'. $ex->getLine(). '|'. $ex->getFile());
        }
    }
}