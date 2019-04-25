<?php

namespace app\common\model\umka;

use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;
use app\common\model\umka\UmkaOrderDetail as UmkaOrderDetailModel;

class UmkaOrder extends ErpModel
{
    use ModelFilter;

    public function scopeOrder(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.account_id', 'in', $params);
        }
    }

    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 关系
     * @return [type] [description]
     */
    public function role()
    {
        //一对一的关系，一个订单对应一个商品
        return $this->belongsTo('WishPlatformOnlineGoods');
    }

    /** 新增订单
     * @param array $data
     * @return false|int
     */
    public function add($order)
    {
        $masterTable = "umka_order";
        $partitionCache = Cache::store('Partition');
        if (!empty($order)) {
            $detailModel = new UmkaOrderDetailModel();
            if (isset($order['order']['create_time'])) {
                if (!$partitionCache->getPartition('UmkaOrder', $order['order']['create_time'])) {
                    $partitionCache->setPartition('UmkaOrder', $order['order']['create_time']);
                }
            }
            if (isset($order['order']['delivery_number'])) { // 启动事务
                Db::startTrans();
                try {
                    if (!$order['order']['id']) {
//                        $order['order']['province']='province';
                        $id = Db::name($masterTable)->insert($order['order'], false, true);
                        if ($id && !empty($order['order_detail'])) {
                            foreach ($order['order_detail'] as $detail) {
                               $detail['oid'] = $id;
                               $detailModel->insert($detail);
                            }
                        }
                    } else {
//                        $order['order']['province']='province';
                        unset($order['order']['create_time']);
                        $id = $order['order']['id'];
                        $this->update($order['order'], [
                            'delivery_number' => $order['order']['delivery_number']
                        ]);
                        foreach ($order['order_detail'] as $detail) {
                            $detailModel->update($detail, ['oid' => $id, 'delivery_number' => $detail['delivery_number']]);
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Cache::handler()->hSet('hash:UmkaOrderFailure', $order['order']['platform_order_no'], $e->getMessage() . $e->getFile() . $e->getLine());
                }
            }
        }
        return true;
    }


    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
        return true;
    }


    /**
     * 修改订单
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检查订单是否存在
     * @return [type] [description]
     */
    protected function checkorder(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return $result;
        }
        return false;
    }
}