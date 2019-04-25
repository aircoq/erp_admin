<?php

namespace app\publish\queue;

use app\publish\service\AmazonPublishConfig;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\service\UniqueQueuer;

class AmazonPublishRelationQueuer extends SwooleQueueJob
{

    public function getName(): string
    {
        return 'amazon刊登-上传产品关系';
    }

    public function getDesc(): string
    {
        return 'amazon刊登-上传产品关系';
    }

    public function getAuthor(): string
    {
        return '冬';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public function init()
    {
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
            $result = $xmlhelp->publishTypeXmlFromSeller($id, 2);
            //$result = $xmlhelp->publishTypeXmlFromAccount($id, 2);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}