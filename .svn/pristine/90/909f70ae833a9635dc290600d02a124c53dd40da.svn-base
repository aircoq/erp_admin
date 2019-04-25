<?php

namespace app\report\task;

use app\index\service\AbsTasker;
use app\common\model\ReportUnshippedByDate;
use app\report\service\WarehousePackageService;
use think\Exception;
use app\common\exception\TaskException;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/1/10
 * Time: 9:36
 */
class WriteBackWarehouseUnShippedTask extends AbsTasker
{
    public function getCreator()
    {
        return 'libaimin';
    }

    public function getDesc()
    {
        return '回写仓库未发货记录';
    }

    public function getName()
    {
        return '回写仓库未发货记录';
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
                ReportUnshippedByDate::add($v['warehouse_id'],$v['channel_id'],$v['qty']);
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
        return true;
    }

    public function getDatas()
    {
        $where['shipping_time'] = 0;
        $list = (new WarehousePackageService())->getPackageCountGroupWarehouseId($where);
        return $list;
    }
}