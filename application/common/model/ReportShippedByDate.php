<?php
namespace app\common\model;


use app\common\model\User;
use erp\ErpModel;

class ReportShippedByDate extends ErpModel
{
    public static function add($warehouseId,$channel_id, $quantity, $time = 0)
    {
        if (!$quantity || !$warehouseId) {
            return false;
        }

        if (!$time) {
            $time = date('Y-m-d');
        } else {
            $time = is_numeric($time) ? date('Y-m-d', $time) : $time;
        }
        $time = strtotime($time);
        $where = [
            'warehouse_id' => $warehouseId,
            'channel_id' => $channel_id,
            'dateline' => $time,
        ];
        $model = new ReportShippedByDate();
        $old = $model->isHas($where);
        if ($old) {
            return $model->save(['quantity' => $quantity], $where);
        } else {
            $where['quantity'] = $quantity;
            return $model->insert($where);
        }
    }

    public function isHas($where)
    {
        $old = $this->where($where)->find();
        if ($old) {
            return $old;
        }
        return false;
    }

}