<?php
namespace app\customerservice\queue;

use think\Exception;
use app\common\service\UniqueQueuer;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\customerservice\service\AmazonFeedbackHelp;
use app\customerservice\queue\AmazonUpdateFeedback;

class AmazonDownFeedbackReport extends  SwooleQueueJob
{
    public $reportPath = ROOT_PATH . 'public/amazon/';
    
    public function getName(): string
    {
        return 'amazon抓取Feedback并保存到文件(队列)';
    }

    public function getDesc(): string
    {
        return 'amazon抓取Feedback并保存到文件(队列)';
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
            $job = $this->params;
            if ($job) {
                set_time_limit(0);
                $help = new AmazonFeedbackHelp;
                $cache = Cache::store('AmazonAccount');

                $accountId = $job['account_id'];
                $reportRequestId = $job['reportRequestId'];
                $accountInfo = $cache->getTableRecord($accountId);
                $apiParams = $help->apiParams($accountInfo);
                $reportIdArr = $help->reportRequestList($apiParams, $reportRequestId);
                if ($reportIdArr) {
                    foreach($reportIdArr as $reportId) {
                        $reportPath = $this->reportPath . 'feedback_'. $reportId. '_'. date('Y-m-d-H-i-s'). '_'. '.xls';
                        $result = $help->saveReport($apiParams, $reportId, $reportPath);

                        if (!$result) {
                            throw new Exception('Save Report Failed! - ' . $reportPath);
                        }
                        $queue = [
                            'account_id' => $accountId,
                            'path' => $reportPath
                        ];
                        (new UniqueQueuer(AmazonUpdateFeedback::class))->push($queue);
                    }
                }
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }

}