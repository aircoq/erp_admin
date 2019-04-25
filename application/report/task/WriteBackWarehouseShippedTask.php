<?php

namespace app\report\task;

use app\index\service\AbsTasker;
use app\common\model\ReportShippedByDate;
use app\report\service\WarehousePackageService;
use think\Exception;
use app\common\exception\TaskException;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/1/10
 * Time: 9:36
 */
class WriteBackWarehouseShippedTask extends AbsTasker
{
    public function getCreator()
    {
        return 'libaimin';
    }

    public function getDesc()
    {
        return '回写仓库已发货记录';
    }

    public function getName()
    {
        return '回写仓库已发货记录';
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
                ReportShippedByDate::add($v['warehouse_id'],$v['channel_id'],$v['qty']);
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }

    public function getDatas()
    {
        $day = date('Y-m-d');
        $b_day = strtotime($day);
        $e_day = $b_day + 86399;
        $where['shipping_time'] = ['between',[$b_day,$e_day]];
        $list = (new WarehousePackageService())->getPackageCountGroupWarehouseId($where);
        return $list;
    }
}