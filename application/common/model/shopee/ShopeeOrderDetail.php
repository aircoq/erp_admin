<?php


namespace app\common\model\shopee;


use erp\ErpModel;


class ShopeeOrderDetail extends ErpModel
{

    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    public function setIsWholesaleAttr($value)
    {
        return $value ? 1 : 0;
    }


}