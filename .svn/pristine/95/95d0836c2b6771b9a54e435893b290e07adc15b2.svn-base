<?php

namespace app\common\model\jumia;

use app\common\cache\Cache;
use think\Db;
use app\common\model\jumia\JumiaOrderDetail as JumiaOrderDetailModel;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

class JumiaOrder extends ErpModel
{
    use ModelFilter;

    public function scopeOrder(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.account_id', 'in', $params);
        }
    }

    static $ORDER_STATUS = [

        6 => 'Cancelled',
        'Acknowledged' => 'Acknowledged',
        'Shipped' => 'shipped'
    ];

    protected $jumiaOrderDetail = '';

    /**
     * 初始化
     *
     * @return [type] [description]
     */
    protected function initialize()
    {
        // 需要调用 mdoel 的 initialize 方法
        parent::initialize();
        if (!$this->jumiaOrderDetail) {
            $this->jumiaOrderDetail = new JumiaOrderDetailModel();
        }
    }

    /**
     * 新增订单
     * @param array $data
     * [description]
     */
    public function add($order)
    {
        $masterTable = "jumia_order";
        $partitionCache = Cache::store('Partition');
        if (!empty($order)) {
//            var_dump($order);die;
            if (isset($order['create_time'])) {
                if (!$partitionCache->getPartition('JumiaOrder', $order['create_time'])) {
                    $partitionCache->setPartition('JumiaOrder', $order['create_time']);
                }
            }
            if (isset($order['order_id'])) {
                try {
                    if (!$order['id']) {
                        $id = Db::name($masterTable)->insert($order, false, true);
                    } else {
                        $id = $order['id'];
                        // 更新
                        $rd = $this->update($order, [
                            'order_id' => $order['order_id']
                        ]);
                    }
                    $info = [
                        'order_updated' => $order['order_updated'],
                        'id' => $id
                    ];
                    Cache::store('JumiaOrder')->orderUpdateTime($order['account_id'], $order['order_id'], $info);
                }catch (\Exception $e){
                    Cache::handler()->hSet('hash:jumia_order:add_error', $order['order_id'] . ' ' . date('Y-m-d H:i:s', time()), 'jumia订单添加异常' . $e->getMessage());

                }

            }
        }

        return true;
    }


    /**
     * 批量新增
     *
     * @param array $data
     * @param array $orderDetail
     *            [description]
     */
    public function addAll(array $data, $orderDetail)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
        $nextOrders = [];
        if (!isset($orderDetail[0])) {
            $nextOrders[] = $orderDetail;
        } else {
            $nextOrders = $orderDetail;
        }
        foreach ($nextOrders as $key => $value) {
            $this->orderDetailFormattingIn($value);
        }
        return true;
    }

    private function setTime($time)
    {
        if (!$time) return 0;
        $second = strtotime($time) + 28800;
        return $second;
    }

    public function orderDetailFormattingIn($orderDetail)
    {
        $nextOrders = [];
        if (!isset($orderDetail['OrderItems']['OrderItem'][0])) {
            $nextOrders[] = $orderDetail['OrderItems']['OrderItem'];
        } else {
            $nextOrders = $orderDetail['OrderItems']['OrderItem'];
        }

        foreach ($nextOrders as $value) {
            if (!isset($value['OrderId'])) {
                continue;
            }
            $one['order_item_id'] = $value['OrderItemId'] ?? '';
            $one['order_id'] = $value['OrderId'] ?? '';
            $one['shop_id'] = $value['ShopId'] ?? '';
            $one['shop_sku'] = $value['ShopSku'] ?? '';
            $one['name'] = $value['Name'] ?? '';
            $one['sku'] = $value['Sku'] ?? '';
            $one['variation'] = $value['Variation'] ?? '';
            $one['currency'] = $value['Currency'] ?? '';
            $one['qty'] = 1;
            $one['item_price'] = $value['ItemPrice'] ?? 0;
            $one['paid_price'] = $value['PaidPrice'] ?? 0;
            $one['wallet_credits'] = $value['WalletCredits'] ?? 0;
            $one['tax'] = $value['TaxAmount'] ?? 0;
            $one['cod_collectable_amount'] = $value['CodCollectableAmount'] ?? 0;
            $one['shipping_amount'] = $value['ShippingAmount'] ?? 0;
            $one['shipping_service_cost'] = $value['ShippingServiceCost'] ?? 0;
            $one['voucher_amount'] = $value['VoucherAmount'] ?? 0;
            $one['voucher_code'] = $value['VoucherCode'] ?? '';
            $one['is_processable'] = $value['IsProcessable'] ?? 0;
            $one['is_digital'] = $value['IsDigital'] ?? 0;
            $one['digital_delivery_info'] = $value['DigitalDeliveryInfo'] ?? '';
            $one['purchase_order_id'] = $value['PurchaseOrderId'] ?? '';
            $one['purchase_order_number'] = $value['PurchaseOrderNumber'] ?? '';
            $one['package_id'] = $value['PackageId'] ?? '';
            $one['extra_attributes'] = $value['ExtraAttributes'] ?? '';
            $one['update_time'] = $this->setTime($value['UpdatedAt'] ?? 0);
            $one['status'] = $value['Status'] ?? '';
            $one['created_time'] = $this->setTime($value['CreatedAt'] ?? 0);
            $one['shipped_date'] = $this->setTime($value['PromisedShippingTimes'] ?? 0);
            $one['tracking_number'] = $value['TrackingCodePre'] ?? '' . $value['TrackingCode'] ?? '';
            $one['shipping_provider'] = $value['ShipmentProvider'] ?? '';
            $one['shipping_provider_type'] = $value['ShippingProviderType'] ?? '';
            $one['shipping_method'] = $value['ShippingType'] ?? '';
            $one['refunded_reason'] = $value['ReasonDetail'] ?? '';
            $one['refunded_note'] = $value['Reason'] ?? '';
            $one['refunded_status'] = $value['ReturnStatus'] ?? '';
            try{
                $this->jumiaOrderDetail->add($one);
            }catch (\Exception $e){
                Cache::handler()->hSet('hash:jumia_order:add_error', $one['order_id'] . ' ' . date('Y-m-d H:i:s', time()), 'jumia订单商品添加异常' . $e->getMessage());
            }

        }
        return true;
    }

    /**
     * 修改订单
     *
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     *
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检查订单是否存在
     *
     * @return [type] [description]
     */
    protected function checkorder(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public function detail()
    {
        return parent::hasMany('JumiaOrderDetail', 'order_id');
    }
}