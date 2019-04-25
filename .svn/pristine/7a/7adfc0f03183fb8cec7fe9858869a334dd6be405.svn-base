<?php

namespace app\publish\queue;

use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\service\CommonQueuer;
use app\publish\service\AmazonPublishConfig;
use think\Db;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\service\UniqueQueuer;

class AmazonPublishPriceQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon刊登-上传产品价格';
    }

    public function getDesc():string
    {
        return 'amazon刊登-上传产品价格';
    }

    public function getAuthor():string
    {
        return '冬';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public function execute()
    {
        set_time_limit(0);
        $id = $this->params;
        if (empty($id)) {
            return;
        }
        try {

            $xmlhelp = new AmazonXsdToXmlService();
            $result = $xmlhelp->publishTypeXmlFromSeller($id, 5);
            //$result = $xmlhelp->publishTypeXmlFromAccount($id, 5);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}