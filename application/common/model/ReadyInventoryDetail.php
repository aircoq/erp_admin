<?php


namespace app\common\model;


use erp\ErpModel;

class ReadyInventoryDetail extends ErpModel
{
    public function getQuantity($id)
    {

    }

    public function plan()
    {
        return $this->belongsTo(PurchasePlan::class,'plan_id','id');
    }
}