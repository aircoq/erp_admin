<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/1/28
 * Time: 11:30
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\publish\service\EbayCtrl;
use app\publish\service\EbayDailyPublishService;
use think\Exception;

class EbayListingExportQueue extends SwooleQueueJob
{
    protected $maxFailPushCount = 0;
    public function getName(): string
    {
        return "eBay listing导出";
    }

    public function getDesc(): string
    {
        return "eBay listing导出";
    }

    public function getAuthor(): string
    {
        return "wlw2533";
    }

    public function execute()
    {
        try {
            $param = $this->params;
            switch ($param['export_type']) {
                case 0:
                    (new EbayCtrl())->onlineExport($param,1);
                    break;
                case 1:
                    (new EbayCtrl())->onlineExportModify($param,1);
                    break;
                case 2:
                    (new EbayCtrl())->doOnlineSpuStatisticExport($param);
                    break;
                case 3:
                    (new EbayDailyPublishService())->doExport($param);
                    break;
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}