<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayMessageQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayMessageHelp;
use think\Exception;


class EbayMessageQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载ebay站内信memberMyssage队列";
    }

    public function getDesc(): string
    {
        return "下载ebay站内信memberMyssage队列";
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

            if (empty($this->params)) {
                return;
            }
            if (!is_array($this->params)) {
                return;
            }

            $account_id = $this->params['account_id'];
            $down_time = $this->params['down_time'];

            set_time_limit(0);
            $account = Cache::store('EbayAccount')->getTableRecord($account_id);
            if (empty($account)) {
                return;
            }
            $data = [
                'userToken' => $account['token'],
                'account_id' => $account['id'],
                'account_name' => strtolower($account['account_name']),

                //开发者帐号相关信息；
                'devID' => $account['dev_id'],
                'appID' => $account['app_id'],
                'certID' => $account['cert_id'],
            ];
            $service = new EbayMessageHelp();
            $service->downMemberMessage($data, $down_time);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}