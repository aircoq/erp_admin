<?php

namespace app\common\model\joom;

use app\order\service\JoomOrderService;
use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

class JoomOrder extends ErpModel
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
        $this->query('set names utf8mb4');
    }



    /** 新增订单
     * @param array $data
     * @return false|int
     */
    public function add(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $masterTable = "joom_order";
        $partitionCache = Cache::store('Partition');
        $order = $data;
        try {
            if ($order['order']['id']) {

                $id = $order['order']['id'];

                (new JoomOrderService())->checkStatusChangeOrder($order['order']);    //当订单状态为refunded时，修改系统订单为需人工审核

                $this->where(['id' => $id,'order_time'=>$order['order']['order_time']])->update($order['order']);

            } else {
                if (!$partitionCache->getPartition('JoomOrder', $order['order']['create_time'])) {
                    $partitionCache->setPartition('JoomOrder', $order['order']['create_time']);
                }
                unset($order['order']['id']);
                $id = Db::name($masterTable)->insert($order['order'], false, true);
            }
            $info = [
                'last_updated' => $order['order']['last_updated'],
                'id' => $id
            ];
            Cache::store('JoomOrder')->orderUpdateTime($order['order']['account_id'], $order['order']['order_id'], $info);
        } catch (Exception $ex) {
            Cache::handler()->hSet('hash:joom_order:add_error', $order['order']['order_id'] . ' ' . date('Y-m-d H:i:s', time()), 'joom订单添加异常' . $ex->getMessage());
        }
        unset($order);

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