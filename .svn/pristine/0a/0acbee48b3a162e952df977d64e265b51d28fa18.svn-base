<?php
namespace app\listing\task;

use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\listing\queue\AmazonRequestListing;
use app\listing\service\AmazonListingHelper;
use app\listing\queue\AmazonRsyncListing as AmazonRsyncListingQueue;
use Waimao\AmazonMws\AmazonReportConfig;


/**
 * @node 实时同步amazonListing
 * Class AmazonRsyncListing
 * packing app\listing\task
 */
class AmazonRsyncListing extends AbsTasker
{
    private $accountList;
    private $redisAmazonAccount;
    private $amazonListingHelper;

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "amazon-listing定时请求";
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "amazon-listing定时请求";
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "冬";
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }


    /**
     * 任务执行内容
     * @return void
     */
    public function execute()
    {
        set_time_limit(0);
        $redisAmazonAccount = Cache::store('AmazonAccount');
        $accountList = $redisAmazonAccount->getTableRecord();
        $queue = new UniqueQueuer(AmazonRequestListing::class);

        foreach ($accountList as $accountInfo) {
            if ($accountInfo['status'] == 0 || $accountInfo['download_listing'] == 0) {
                continue;
            }

            //最后下载时间；
            $lastRequestTime = $redisAmazonAccount->getListingLastRequestTime($accountInfo['id']);
            if (!empty($lastRequestTime)) {
                //当有最后下载时间，最后下载时间 + 间隔时间 大于当前时间时，说明下载时间还没有到，应该跳过；减60秒是防止因task定时没精确到秒时出现问题；
                if ($lastRequestTime + $accountInfo['download_listing'] * 60 - 60 > time()) {
                    continue;
                }
            }

            $queue->push($accountInfo['id']);
        }
    }
}
