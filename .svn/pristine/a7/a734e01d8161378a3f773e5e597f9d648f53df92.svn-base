<?php

namespace app\publish\queue;

use think\Exception;
use app\common\service\UniqueQueuer;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\listing\service\AmazonListingHelper;
use Waimao\AmazonMws\AmazonReportConfig;


class AmazonBrowseTreeQueuer extends SwooleQueueJob
{
    public $reportPath = ROOT_PATH . 'public/amazon/';

    public function getName(): string
    {
        return 'amazon抓取分类树并保存到文件(队列)';
    }

    public function getDesc(): string
    {
        return 'amazon抓取分类树并保存到文件(队列)';
    }

    public function getAuthor(): string
    {
        return '翟彬';
    }

    public function init()
    {
    }

    public function execute()
    {
        try {
            $job = $this->params;
            if (!$job) {
                throw new Exception('信息不对');
            }
            set_time_limit(0);
            $accountId = $job['account_id'];
            $reportRequestId = $job['reportRequestId'];

            $amazonListingHelper = new AmazonListingHelper();
            $amazonListingHelper->setReportType(AmazonReportConfig::REPORT_TYPE_XML_BROWSE_TREE_DATA);
            $accountInfo = Cache::store('AmazonAccount')->getTableRecord($accountId);

            $apiParams = $amazonListingHelper->apiParams($accountInfo);
            $reportIdArr = $amazonListingHelper->reportRequestList($apiParams, $reportRequestId);

            if (!empty($reportIdArr)) {
                $reportId = $reportIdArr[0];
                $reportPath = $this->reportPath . 'browserTree_' . $accountInfo['site'] . date('Y-m-d-H-i-s') . '.xml';
                $result = $amazonListingHelper->saveReport($apiParams, $reportId, $reportPath);
                if (!$result) {
                    throw new Exception('Save Report Failed! - ' . $reportPath);
                }
                $queue = [
                    'account_id' => $accountId,
                    'path' => $reportPath
                ];
                //echo 'Successfully! - account_id'. $accountId. '-'. $reportPath;
                (new UniqueQueuer(AmazonBrowseTreeSaveQueuer::class))->push($queue);
            } else {
                throw new Exception('reportId 没有生成!');
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }

}