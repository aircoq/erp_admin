<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/4/26
 * Time: 18:12
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;
use app\index\service\EbayAccountService;
use think\Exception;

class CheckEbayTokenQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "检查ebay账号token是否可用";
    }

    public function getDesc(): string
    {
        return "检查ebay账号token是否可用";
    }

    public function getAuthor(): string
    {
        return "donghaibo";
    }

    public function execute()
    {
        try{
            $params = $this->params;
            $config = $params['config'];
            $account_id = $params['account_id'];
            $service = new EbayAccountService();
            $service->checkTokenExpire($account_id,$config);
        }catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }


}