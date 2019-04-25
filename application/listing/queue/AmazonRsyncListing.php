<?php
namespace app\listing\queue;

use think\Exception;
use app\common\service\SwooleQueueJob;
use app\listing\service\AmazonListingHelper;

class AmazonRsyncListing extends  SwooleQueueJob
{
    public $reportPath = ROOT_PATH . 'public/amazon/';
    
    public function getName(): string
    {
        return 'amazon-listing下载数据解析保存-1';
    }

    public function getDesc(): string
    {
        return 'amazon-listing下载数据解析保存-1';
    }

    public function getAuthor(): string {
        return '冬';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 15;
    }

    public function execute()
    {
        try {
            $job = $this->params;
            if ($job) {
                set_time_limit(0);
                $amazonListingHelper = new AmazonListingHelper;
                $amazonListingHelper->syncListing($job);
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }
}