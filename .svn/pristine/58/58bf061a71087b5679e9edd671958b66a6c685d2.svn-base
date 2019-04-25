<?php
/**
 * Created by Phpstom.
 * User: YangJiafei
 * Date: 2019/4/18
 * Time: 18:17
 */


namespace app\finance\queue;


use app\common\service\SwooleQueueJob;
use app\finance\service\FinancePurchaseRecordExport;
use think\Request;

class FinancePurchaseRecordExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "采购核算单导出";
    }

    public function getDesc(): string
    {
        return "采购核算单导出";
    }

    public function getAuthor(): string
    {
        return "YangJiafei";
    }

    public function execute()
    {
        try {
            $service = new FinancePurchaseRecordExport();
            $service->initParam($this->params);
            $service->queueExport($this->params['apply_id'], $this->params['file'], 1);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}