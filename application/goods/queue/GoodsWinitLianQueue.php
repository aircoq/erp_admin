<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/28
 * Time: 16:33
 */

namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsWinitLian as GoodsWinitLianService;

class GoodsWinitLianQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "更新万邑链产品列表";
    }

    public function getDesc(): string
    {
        return "更新万邑链产品列表";
    }

    public function getAuthor(): string
    {
        return "Yu";
    }

    public function execute()
    {
        $param = $this->params;

        $pageSize = 200;//每页的查询数，api有最多200的限制
        $pageIndex = 1;//查询页码
        $service = new GoodsWinitLianService();
        $warehouseIds = $service->getWarehouseIds();

        $updateStartDate = $param['updateStartDate'];
        $updateEndDate = $param['updateEndDate'];

        foreach($warehouseIds as $id){
            $config = $service->getConf($id);
            $data = $service->getGoodsList($config,$updateStartDate,$updateEndDate,$pageIndex,$pageSize);

            if (!empty($data)){
                $service->dataHandle($data,$id);
                $totalCount = $data['data']['pageParams']['totalCount'];
                $pageNum = ceil($totalCount/$pageSize);
                if ($pageNum>1){
                    for ($i=2;$i<$pageNum+1;$i++){
                        $data = $service->getGoodsList($config,$updateStartDate,$updateEndDate,$i,$pageSize);
                        $service->dataHandle($data,$id);
                    }
                }
            }
        }
    }
}