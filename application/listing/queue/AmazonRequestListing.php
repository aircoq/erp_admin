<?php
namespace app\listing\queue;

use think\Exception;
use app\common\service\UniqueQueuer;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\listing\service\AmazonListingHelper;
use Waimao\AmazonMws\AmazonReportConfig;

class AmazonRequestListing extends  SwooleQueueJob
{
    public $reportPath = ROOT_PATH . 'public/amazon/';
    
    public function getName(): string
    {
        return 'amazon-listing请求reportRequestId';
    }

    public function getDesc(): string
    {
        return 'amazon-listing请求reportRequestId';
    }

    public function getAuthor(): string {
        return '冬';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        try {
            $account_id = $this->params;
            if (!is_numeric($account_id)) {
                return false;
            }

            $amazonListingHelper = new AmazonListingHelper;
            $amazonListingHelper->setReportType(AmazonReportConfig::REPORT_TYPE_LISTINGS_DATA);

            $redisAmazonAccount = Cache::store('AmazonAccount');
            $accountInfo = $redisAmazonAccount->getAccount($account_id);
            if (empty($accountInfo)) {
                return false;
            }

            $lastRequestTime = $redisAmazonAccount->getListingLastRequestTime($accountInfo['id']);

            //没有最后下载时间，则换当前时间来算；
            if (empty($lastRequestTime)) {
                $lastRequestTime = strtotime('-90 days');
            } else {
                $lastRequestTime = $lastRequestTime - 3600;
            }

            $timeLimits = [
                'StartDate' => $amazonListingHelper->getFormattedTimestamp($lastRequestTime),
                'EndDate' => $amazonListingHelper->getFormattedTimestamp(time())
            ];

            $apiParams = $amazonListingHelper->apiParams($accountInfo);
            $reportRequestId = $amazonListingHelper->requestReport($apiParams, $timeLimits);

            if ($reportRequestId > 0) {
                $redisAmazonAccount->setListingLastRequestTime($accountInfo['id'], time());
                $queue = [
                    'account_id' => $accountInfo['id'],
                    'reportRequestId' => $reportRequestId
                ];
                if ($accountInfo['id'] % 4 == 1) {
                    (new UniqueQueuer(AmazonRsyncListing::class))->push($queue, 60 * 10);
                } else if ($accountInfo['id'] % 4 == 2) {
                    (new UniqueQueuer(AmazonRsyncListing2::class))->push($queue, 60 * 10);
                } else if ($accountInfo['id'] % 4 == 3) {
                    (new UniqueQueuer(AmazonRsyncListing3::class))->push($queue, 60 * 10);
                } else {
                    (new UniqueQueuer(AmazonRsyncListing4::class))->push($queue, 60 * 10);
                }
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }
}