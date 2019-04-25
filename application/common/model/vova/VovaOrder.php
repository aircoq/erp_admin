<?php
namespace app\common\model\vova;

use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use app\common\model\vova\VovaOrder as VovaOrderModel;
use think\db\Query;
use app\order\service\VovaOrderService;

class VovaOrder extends ErpModel
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
        return $this->belongsTo('VovaPlatformOnlineGoods');
    }

    /**
     * 新增订单
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (empty($data)) {

            return false;
        }
        $masterTable = "vova_order";
        $partitionCache = Cache::store('Partition');
        foreach ($data as $order) {

            Db::startTrans();
            try {
                if ($order['order']['id']) {

                    /*
                     * 检查订单是否需要拉入人工审核
                     */
                    (new VovaOrderService())->checkStatusChangeOrder($order['order']);

                    $id = $order['order']['id'];
                    $this->where(['id' => $id])->update($order['order']);
                }else {
                    if (!$partitionCache->getPartition('VovaOrder', $order['order']['confirm_time'])) {
                        $partitionCache->setPartition('VovaOrder', $order['order']['confirm_time']);
                    }
                    unset($order['order']['id']);
                    (new VovaOrderModel())->allowField(true)->save($order['order']);
                    //$id = Db::name($masterTable)->insert($order['order'], false, true);
            }
                Db::commit();
            } catch (Exception $ex) {
                Db::rollback();
                Cache::handler()->hSet('hash:vova_order:add_error', $order['order']['order_sn'] . ' ' . date('Y-m-d H:i:s', time()), 'vova订单添加异常'. $ex->getMessage());
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
        return $this->hasMany('vova_order_detail', 'vova_order_id', 'id')->field(true);
    }


}