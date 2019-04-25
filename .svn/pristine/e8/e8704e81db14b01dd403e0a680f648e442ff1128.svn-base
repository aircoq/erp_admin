<?php
namespace  app\customerservice\queue;

use app\common\service\SwooleQueueJob;
use app\customerservice\service\aliexpress\SynAliexpressMsg;
use think\Exception;

 /**
 * Created by phpstorm
 * User: laiyongfeng
 * Date: 2018/1/15
 * Time: 11:25
 */

class AliexpressMsgQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "抓取速卖通站内信";
    }

    public function getDesc(): string
    {
        return "抓取速卖通站内信";
    }

    public function getAuthor(): string
    {
        return "laiyongfeng";
    }

    public function execute()
    {
        try {
            $params = $this->params;
            $service = new SynAliexpressMsg();
            $service->downMsg($params['config'], $params['msgType']);
        }catch (\Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}