<?php
namespace app\listing\queue;

use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonListingService;
use think\Exception;

class AmazonUpdateListingSkuMap extends  SwooleQueueJob
{
    public function getName(): string {
        return 'amazon查找listing的SKU映射并插入到listing表(队列)';
    }

    public function getDesc(): string {
        return 'amazon查找listing的SKU映射并插入到listing表(队列)';
    }

    public function getAuthor(): string {
        return '翟彬';
    }

    public function init()
    {

    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $job = $this->params;

            $listing_id = 0;
            if (is_array($job)) {
                $listing_id = $job['listing_id'] ?? 0;
            } else if (is_numeric($job)) {
                $listing_id = $job;
            }

            if ($job) {
                return (new AmazonListingService())->userRelation($listing_id);
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }
}