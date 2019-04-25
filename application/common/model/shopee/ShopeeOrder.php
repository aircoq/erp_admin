<?php


namespace app\common\model\shopee;

use erp\ErpModel;

class ShopeeOrder extends ErpModel
{

    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }
    const PAYMENT_METHOD = [
        'PAY_COD' => 1,
        'PAY_BANK_TRANSFER' => 2,
        'PAY_SHOPEE_WALLET' => 3,
        'PAY_BANK_TRANSFER' => 4,
        'PAY_OFFLINE_PAYMENT' => 5,
        'PAY_IPAY88' => 6,
        'PAY_FREE' => 7,
        'PAY_ESUN' => 8,
        'PAY_BILL_PAYMENT' => 9,
        'PAY_INDOMARET' => 10,
        'PAY_KREDIVO' => 11
    ];

    const ORDER_STATUS = [
        'UNPAID' => 1,
        'READY_TO_SHIP' => 2,
        'SHIPPED' => 3,
        'TO_CONFIRM_RECEIVE' => 4,
        'CANCELLED' => 5,
        'INVALID' => 6,
        'TO_RETURN' => 7,
        'COMPLETED' => 8,
        'IN_CANCEL' => 9,
        'RETRY_SHIP' => 10
    ];

    const ORDER_CANCEL_REASON = [
        'OUT_OF_STOCK' => '',
        'CUSTOMER_REQUEST' => '',
        'UNDELIVERABLE_AREA' => '',
        'COD_NOT_SUPPORTED' => ''
    ];

    public function setPaymentMethodAttr($value)
    {
        return isset(self::PAYMENT_METHOD[$value]) ? self::PAYMENT_METHOD[$value] : 0;
    }

    public function setCodAttr($value)
    {
        if ($value == true) {
            return 1;
        }
        return 0;
    }

    public function setOrderStatusAttr($value)
    {
        return isset(self::ORDER_STATUS[$value]) ? self::ORDER_STATUS[$value] : 0;
    }

    public function account()
    {
        return $this->belongsTo(ShopeeAccount::class, 'account_id', 'id');
    }

    public function detail()
    {
        return $this->hasMany(ShopeeOrderDetail::class, 'shopee_order_id', 'id');
    }

    public function getLastTimeAttr($value, $data)
    {
        return $data['create_time'] + $data['days_to_ship'] * 86400;
    }

    public function getPaymentMethodTxtAttr($value, $data)
    {
        $ret = array_flip(self::PAYMENT_METHOD);
        return isset($ret[$data['payment_method']]) ? $ret[$data['payment_method']] : '';
    }

    public function getOrderStatusTxtAttr($value, $data)
    {
        $ret = array_flip(self::ORDER_STATUS);
        return isset($ret[$data['order_status']]) ? $ret[$data['order_status']] : '';
    }




}