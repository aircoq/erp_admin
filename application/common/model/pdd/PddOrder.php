<?php

namespace app\common\model\pdd;

use app\common\cache\Cache;
use think\Db;
use app\common\service\UniqueQueuer;
use app\common\model\pdd\PddOrderDetail as PddOrderDetailModel;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

class PddOrder extends ErpModel
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
        $masterTable = "pdd_order";
        $detailTable = "pdd_order_detail";
        $partitionCache = Cache::store('Partition');
        if (!empty($order)) {
            $detailModel = new PddOrderDetailModel();
            if(isset($order['order']['created_time'])) {
                if (!$partitionCache->getPartition('PddOrder', $order['order']['created_time'])) {
                    $partitionCache->setPartition('PddOrder', $order['order']['created_time']);
                }
            }

            if (isset($order['order']['order_sn'])) { // 启动事务
                Db::startTrans();
                try {
                    if (!$order['order']['id']) {
                        $id =Db::name($masterTable)->insert($order['order'], false, true);
                        if ($id && !empty($order['order_detail'])) {
                            foreach ($order['order_detail'] as $detail) {
                                $detail['oid'] = $id;
                                $detailModel->insert($detail);
                            }

                        }
                    } else {
                        $id = $order['order']['id'];
                        $rd = $this->update($order['order'], [
                            'order_sn' => $order['order']['order_sn'],
                            'account_id' => $order['order']['account_id']
                        ]);

                        /*foreach ($order['order_detail'] as $detail) {
                            $detailModel->update($detail, ['oid' => $order['order']['id'], 'order_sn' => $detail['order_sn']]);
                        }*/
                    }
                    Db::commit();
//                    $info = [
//                        'created_time' => $order['order']['created_time'],
//                        'id' => $id
//                    ];
//                    Cache::store('PddOrder')->orderUpdateTime($order['order']['account_id'], $order['order']['order_sn'], $info);
                } catch (\Exception $e) {
                   // var_dump($e);die;
                    // 回滚事务
                    Db::rollback();
                    Cache::handler()->hSet('hash:PddOrderFailure', $order['order']['order_sn'], $e->getMessage());
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
//        $listModel = new PddListingModel();
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
            //var_dump($value);die;
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
        return parent::hasMany('pddOrderDetail', 'order_id');
    }
}