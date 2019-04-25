<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\publish\service\AmazonPublishHelper;

class AmazonPublishSysncQueuer extends  SwooleQueueJob {

    private $accountCache;

    private $redisAmazonAccount;

    public function getName():string
    {
        return 'amazon刊登完成-下载产品数据';
    }

    public function getDesc():string
    {
        return 'amazon刊登完成-同步产品数据，刊登完成后，不会立刻去拉listing，会启动此应用把本次刊登的产品同步下来';
    }

    public function getAuthor():string
    {
        return '冬';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function init()
    {
        $this->accountCache = Cache::store('Account');
        $this->redisAmazonAccount = Cache::store('AmazonAccount');
    }

    public function execute()
    {
        set_time_limit(0);
        $id = $this->params;
        if (!is_numeric($id)) {
            throw new Exception('同步Listing产品数据发错错误，无效publish_Product_id');
        }
        try{
            //以下开始上传XML，并保存提交结果ID；
            $publishHelper  = new AmazonPublishHelper();
            $publishHelper->updateListingFromProductId($id);
        } catch (QueueException $exp){
            throw  new Exception($exp->getMessage().$exp->getFile().$exp->getLine());
        } catch (Exception $exp) {
            throw  new Exception($exp->getMessage().$exp->getFile().$exp->getLine());
        }
    }

}