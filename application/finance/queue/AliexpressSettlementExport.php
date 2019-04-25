<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/4/13
 * Time: 9:25
 */

namespace app\finance\queue;


use app\common\service\SwooleQueueJob;
use app\finance\service\AliexpressSettlementService;
use think\Exception;

class AliexpressSettlementExport extends SwooleQueueJob
{

    public function getName(): string
    {
        return "速卖通资金核算导出";
    }

    public function getDesc(): string
    {
        return "速卖通资金核算导出";
    }

    public function getAuthor(): string
    {
        return "donghaibo";
    }

    public function execute()
    {
        try{
          $params = $this->params;
          $service = new AliexpressSettlementService();
          $service->export($params);
        }catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }
}