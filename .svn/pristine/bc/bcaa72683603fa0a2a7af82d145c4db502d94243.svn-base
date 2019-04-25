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
use app\common\service\SwooleQueueJob;
use app\customerservice\service\EbayFeedbackHelp;
use think\Exception;


class EbayFeedbackQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载ebay评价队列";
    }

    public function getDesc(): string
    {
        return "下载ebay评价队列";
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
            } else {
                $account_id = $this->params['account_id'];
                $down_time = $this->params['down_time'];
            }
            $account = Cache::store('EbayAccount')->getTableRecord($account_id);
            if (empty($account)) {
                return;
            }
            $execute_start = time();

            $service = new EbayFeedbackHelp();
            $total = 0;
            //再下载买家发出去的评价；
            $total += $service->FeedBackLeftAsSeller($account_id, $down_time);
            //先下载这段时间做为卖家接收到的评价，
            $total += $service->FeedBackReceivedAsSeller($account_id, $down_time);


            // 存入缓存. 只要远程获取了，就算没数据也更新时间。
            $newStartTime = date('Y-m-d H:i:s', $execute_start);
            $time_array = [
                'last_update_time' => $newStartTime,
                'last_download_time' => date('Y-m-d H:i:s'),
                'download_number' => $total,
                'download_execute_time' => time() - $execute_start
            ];
            Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'feedback', $time_array);

        }catch (Exception $ex){
            throw new Exception($ex->getMessage(). '|'. $ex->getLine(). '|'. $ex->getFile());
        }
    }
}