<?php

namespace app\common\model\pandao;

use app\order\service\PandaoOrderService;
use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

class PandaoOrder extends ErpModel
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
    public function add(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $masterTable = "pandao_order";
        $partitionCache = Cache::store('Partition');
        $order = $data;
        try {
            if ($order['order']['id']) {
                $id = $order['order']['id'];

                /** mymall订单状态改变时推送到人工审核 */
                (new PandaoOrderService())->checkStatusChangeOrder($order['order']);

                $this->where(['id' => $id])->update($order['order']);

            } else {
                if (!$partitionCache->getPartition('PandaoOrder', $order['order']['create_time'])) {
                    $partitionCache->setPartition('PandaoOrder', $order['order']['create_time']);
                }
                unset($order['order']['id']);
                $id = Db::name($masterTable)->insert($order['order'], false, true);
            }
            $info = [
                'last_updated' => $order['order']['last_updated'],
                'id' => $id
            ];
            Cache::store('PandaoOrder')->OrderUpdateTime($order['order']['account_id'], $order['order']['order_id'], $info);
        } catch (Exception $ex) {
            Cache::handler()->hSet('hash:pandao_order:add_error', $order['order']['order_id'] . ' ' . date('Y-m-d H:i:s', time()), 'pandao订单添加异常' . $ex->getMessage());
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