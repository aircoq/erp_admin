<?php
namespace app\common\model\voma;

use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use app\common\model\voma\VomaOrder as VomaOrderModel;
use app\common\model\voma\VomaOrderDetail as VomaOrderDetailModel;
use think\db\Query;
class VomaOrder extends ErpModel
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
        return $this->belongsTo('VomaPlatformOnlineGoods');
    }

    /**
     * 新增订单
     * @param array $data [description]
     */
    public function add(array $data)
    {
       // var_dump($data);die;
        if (empty($data)) {

            return false;
        }
        $masterTable = "voma_order";
        $partitionCache = Cache::store('Partition');
        foreach ($data as $order) {
            Db::startTrans();
            try {
                if ($order['order']['id']) {
                    $id = $order['order']['id'];
                    $this->where(['id' => $id])->update($order['order']);
                }else {
                    if (!$partitionCache->getPartition('VomaOrder', $order['order']['confirm_time'])) {
                        $partitionCache->setPartition('VomaOrder', $order['order']['confirm_time']);
                    }
                    unset($order['order']['id']);
                    $id = Db::name($masterTable)->insert($order['order'], false, true);


            }

                Db::commit();
                $info = [
                    'confirm_time' => $order['order']['confirm_time'],
                    'id'  => $id,
                    'nowtime'  => date('Y-m-d H:i:s',time())
                ];
                Cache::store('vomaOrder')->OrderUpdateTime($order['order']['account_id'], $order['order']['order_sn'], $info);
            } catch (Exception $ex) {
                Db::rollback();
                Cache::handler()->hSet('hash:voma_order:add_error', $order['order']['order_sn'] . ' ' . date('Y-m-d H:i:s', time()), 'voma订单添加异常'. $ex->getMessage());
            }
            unset($order);
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

    public function skuList()
    {
        return $this->hasMany('voma_order_detail', 'voma_order_id', 'id')->field(true);
    }


}