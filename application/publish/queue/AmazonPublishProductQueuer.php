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

class AmazonPublishProductQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon刊登-上传产品数据';
    }

    public function getDesc():string
    {
        return 'amazon刊登-上传产品数据';
    }

    public function getAuthor():string
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
        //检测参数，如果不是数字，则停止；
        $id = $this->params;
        if (empty($id) || !is_numeric($id)) {
            return false;
        }

        try{
            set_time_limit(0);
            $xmlhelp = new AmazonXsdToXmlService();
            $result = $xmlhelp->publishTypeXmlFromSeller($id, 1);
            //$result = $xmlhelp->publishTypeXmlFromAccount($id, 1);
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}