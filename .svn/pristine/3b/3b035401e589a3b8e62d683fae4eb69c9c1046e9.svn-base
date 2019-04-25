<?php
namespace app\customerservice\task;

use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use think\Exception;
use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\customerservice\service\AmazonFeedbackHelp;
use app\customerservice\queue\AmazonDownFeedbackReport;

class AmazonFeedback extends AbsTasker
{
    public function getCreator() {
        return '冬';
    }
    
    public function getDesc() {
        return '抓取亚马逊中差评';
    }
    
    public function getName() {
        return '抓取亚马逊中差评';
    }
    
    public function getParamRule() {
        return [];
    }

    public function __construct() {
    }
    
    public function execute() {
        set_time_limit(0);
        try {
            $time = time();
            $cache = Cache::store('AmazonAccount');
            $help = new AmazonFeedbackHelp();

            $accountList = $cache->getTableRecord();
            foreach ($accountList as $v) {
                if (empty($v['secret_key']) || empty($v['access_key_id']) || empty($v['merchant_id'])) {
                    continue;
                }
                //停用、未授权，或者授权信息不全跳过；
                if ($v['status'] == 0 || $v['is_authorization'] == 0 || empty($v['sync_feedback'])) {
                    continue;
                }

                //上一次抓取成功的时间；
                $lastRequestTime = $cache->getFeedbackLastRequestTime($v['id']);
                //上时次抓取时间存在，且与现在时间相差没超过设定时间，则跳过
                if ($lastRequestTime != 0 && $time - $lastRequestTime < $v['sync_feedback'] * 60) {
                    continue;
                }

                $start = !empty($lastRequestTime) ? $lastRequestTime - 3600 * 2 : $time - 86400 * 180;
                $timeLimits = [
                    'StartDate' => $help->getFormattedTimestamp($start),
                    'EndDate' => $help->getFormattedTimestamp($time)
                ];

                $apiParams = $help->apiParams($v);
                $reportRequestId = $help->requestReport($apiParams, $timeLimits);
                if ($reportRequestId > 0) {
                    $cache->setFeedbackLastRequestTime($v['id'], $time);
                    $queue = [
                        'account_id' => $v['id'],
                        'reportRequestId' => $reportRequestId
                    ];
                    (new UniqueQueuer(AmazonDownFeedbackReport::class))->push($queue, 60 * 10);
                }
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }

}