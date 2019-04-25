<?php
namespace app\common\model\wish;

use erp\ErpModel;

class WishSettlement extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function getShippingStatusTxtAttr($value, $data)
    {
        if ($data['shipping_status'] == 1) {
            return '全部发货';
        }
        if ($data['shipping_status'] == 0) {
            return '未发货';
        }
        if ($data['shipping_status'] == 2) {
            return '部分发货';
        }
    }
}