<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 11:41
 */

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use app\publish\service\PandaoService;
use think\Exception;

class PandaoExportListingQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'MyMallListing导出';
    }

    public function getDesc(): string
    {
        return 'MyMallListing导出';
    }

    public function getAuthor(): string
    {
        return 'qing';
    }

    public function execute()
    {
        try{
            $service = new PandaoService();
            $service->allExport($this->params);
        }catch (\Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}