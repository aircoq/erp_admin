<?php

namespace app\report\task;

use app\index\service\AbsTasker;
use app\common\model\ReportShortageByDate;
use app\report\service\WarehousePackageService;
use think\Exception;
use app\common\exception\TaskException;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/1/10
 * Time: 9:36
 */
class WriteBackWarehouseShortageTask extends AbsTasker
{
    public function getCreator()
    {
        return 'libaimin';
    }

    public function getDesc()
    {
        return '回写仓库缺货记录';
    }

    public function getName()
    {
        return '回写仓库缺货记录';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $list = $this->getDatas();
            foreach ($list as $v){
                ReportShortageByDate::add($v['warehouse_id'],$v['channel_id'],$v['qty']);
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
        return true;
    }

    public function getDatas()
    {
        $where =  '(status != 0 and (status >> 21) = 1 and status != 4294967295)';
        $whereAnd = [
            'shipping_time' => 0,
        ];
        $list = (new WarehousePackageService())->getPackageCountGroupWarehouseId($where,$whereAnd);
        return $list;
    }
}