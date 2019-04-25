<?php

namespace app\common\model\cd;

use app\common\cache\Cache;
use think\Db;
use app\common\service\UniqueQueuer;
use app\common\model\cd\CdOrderDetail as CdOrderDetailModel;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

class CdOrder extends ErpModel
{
    use ModelFilter;

    public function scopeOrder(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.account_id', 'in', $params);
        }
    }

    static $ORDER_STATUS = [
        0 => '0',
        2 => 'Acknowledgment',
        3 => '3',
        5 => 'Shipment',
        6 => 'Failure',
        7 => 'Delivered',
        8 => 'Cancelled',
        12 => 'Refunded',
        13 => 'Ready to Ship',
        15 => 'Shipped',
        17 => 'Return Requested',
        18 => 'Returned',
        23 => 'Shipment Created',
        25 => 'Manifest Requested',
        26 => '26',
        27 => '27',
    ]
    ;

    /**
     * 初始化
     *
     * @return [type] [description]
     */
    protected function initialize()
    {
        // 需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 新增订单
     * @param array $data
     * [description]
     */
    public function add($order)
    {
        $masterTable = "cd_order";
        $detailTable = "cd_order_detail";
        $partitionCache = Cache::store('Partition');
        if (!empty($order)) {
            $detailModel = new CdOrderDetailModel();
            if(isset($order['order']['create_time'])) {
                if (!$partitionCache->getPartition('CdOrder', $order['order']['create_time'])) {
                    $partitionCache->setPartition('CdOrder', $order['order']['create_time']);
                }
            }

            if (isset($order['order']['order_id'])) { // 启动事务
                Db::startTrans();
                try {
                    $itemIds = [];
                    if (!$order['order']['id']) {
                        $id = Db::name($masterTable)->insert($order['order'], false, true);
                        if ($id && !empty($order['orderDetail'])) {
                            foreach ($order['orderDetail'] as $detail) {
                                $itemIds[] = $detail['record_number'];
                                $detail['cd_order_id'] = $id;
                                $detailModel->insert($detail);
                            }
                        }
                    } else {
                        $id = $order['order']['id'];
                        // 更新
                        $rd = $this->update($order['order'], [
                            'order_id' => $order['order']['order_id']
                        ]);
                        foreach ($order['orderDetail'] as $detail) {
                            $detailModel->update($detail, ['cd_order_id' => $order['order']['id'], 'sku' => $detail['sku']]);
                            $itemIds[] = $detail['record_number'];
                        }
                    }
                    Db::commit();
                    $info = [
                        'order_updated' => $order['order']['order_updated'],
                        'id' => $id
                    ];
//                    Cache::store('CdOrder')->orderUpdateTime($order['order']['account_id'], $order['order']['order_id'], $info);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Cache::handler()->hSet('hash:CdOrderFailure', $order['order']['order_id'], $e->getMessage());
                }
            }
        }

        return true;
    }

    public function updateLastShipTime($orderitem)
    {
        $order = $this->where('id', $orderitem['id'])->find();
        if (!$order) {
            return;
        }
        // 从listing中获取最迟发货时间
//        $listModel = new CdListingModel();
//        $listing = $listModel->where(['record_number' => ['in', $orderitem['itemIds']]])->field('id,min(dispatch_max_time) mindate')->find();
//
//        $upload_time = !empty($listing['mindate']) ? 86400 * intval($listing['mindate']) : 86400 * 2;
//        $payment_time = empty($order['paid_time'])? $order['create_time'] : $order['paid_time'];
//        $this->update(['latest_ship_time' => $payment_time + $upload_time], ['id' => $orderitem['id']]);
    }

    /**
     * 批量新增
     *
     * @param array $data
     *            [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
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
        return parent::hasMany('CdOrderDetail', 'order_id');
    }
}