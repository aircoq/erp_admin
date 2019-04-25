<?php
namespace app\publish\task;

use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\listing\service\AmazonListingHelper;
use Waimao\AmazonMws\AmazonReportConfig;
use app\publish\queue\AmazonBrowseTreeQueuer;

 
/**
 * @node 定时获取Aamazon分类树
 * Class AmazonRsyncListing
 * packing app\listing\task
 */
class AmazonBrowseTree extends AbsTasker
{

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "amazon分类树定时请求";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "amazon分类树定时请求";
    }
    
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "翟彬";
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
    public  function execute()
    {
        set_time_limit(0);
        $accountList = Cache::store('AmazonAccount')->getTableRecord();
        $amazonListingHelper = new AmazonListingHelper;
        $amazonListingHelper->setReportType(AmazonReportConfig::REPORT_TYPE_XML_BROWSE_TREE_DATA);

        // 'UK' => 268, 'fr' => 271, 'US' => 267, 'DE' => 269, 'es' => 270, 'ca' => 321, 'it' => 331, 'jp' => 334, 'MX' => 874  'AU' => 889, 'IN' => 2764

        foreach ($accountList as $accountInfo) {
            if (!in_array($accountInfo['id'], [267, 268, 269, 270, 271, 321, 331, 334, 874, 894, 2764])) {
                continue;
            }

            $timeLimits = [
                'StartDate' => $amazonListingHelper->getFormattedTimestamp(time()-90*24*60*60),
                'EndDate' => $amazonListingHelper->getFormattedTimestamp(time())
            ];

            $apiParams = $amazonListingHelper->apiParams($accountInfo);

            $reportRequestId = $amazonListingHelper->requestReport($apiParams, $timeLimits);

            if ($reportRequestId > 0) {
                $queue = [
                    'account_id'=>$accountInfo['id'],
                    'reportRequestId'=>$reportRequestId
                ];
                //var_dump($accountInfo['site'], $queue, "\r\n\r\n");
                (new UniqueQueuer(AmazonBrowseTreeQueuer::class))->push($queue, 60*10);
            }
        }
    }
}
