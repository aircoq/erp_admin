<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-6-7
 * Time: 下午2:47
 */

namespace app\common\traits;


trait Warehouse
{
    private $warehouseServer = null;

    private function getWarehouse($warehouseId)
    {
        if(!$this->warehouseServer){
            $this->warehouseServer = new \app\warehouse\service\Warehouse();
        }
        return $this->warehouseServer->getWarehouse2($warehouseId);
    }
}