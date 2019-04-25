<?php
namespace app\common\model\lazada;
use app\order\service\LazadaService;
use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use app\common\model\lazada\LazadaOrder as LazadaOrderModel;
use think\db\Query;
class LazadaOrder extends ErpModel
{
    use ModelFilter;
    public function scopeOrder(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.lazada_account_id', 'in', $params);
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

    /**
     * 关系
     * @return [type] [description]
     */
    public function role()
    {
        //一对一的关系，一个订单对应一个商品
        return $this->belongsTo('LazadaPlatformOnlineGoods');
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
        $masterTable = "lazada_order";
        $detailTable = "lazada_order_detail";
        $partitionCache = Cache::store('Partition');
        $lazadaOrder= new LazadaOrderModel();
        $lazadaOrderDetail=new LazadaOrderDetail();
        foreach ($data as $order) {
            Db::startTrans();
            try {
                if ($order['order']['id']) {
                    $id = $order['order']['id'];
                    unset($order['order']['create_time']);
                    $this->where(['id' => $id])->update($order['order']);
                    foreach ($order['orderDetail'] as $detail) {
                        $lazadaOrderDetail->where(['oid' => $id, 'order_id' => $detail['order_id'],'order_item_id'=>$detail['order_item_id']])->update($detail);
                    }
                }else {
                    if (!$partitionCache->getPartition('LazadaOrder', $order['order']['created_at'])) {
                        $partitionCache->setPartition('LazadaOrder', $order['order']['created_at']);
                    }
                    unset($order['order']['id']);
                    $id = Db::name($masterTable)->insert($order['order'], false, true);
                    foreach ($order['orderDetail'] as $detail) {
                        $detail['oid'] = $id;
                        (new LazadaOrderDetail())->allowField(true)->save($detail);
                    }
                }
                Db::commit();
            } catch (Exception $ex) {
                Db::rollback();
                $error_msg = '单号:' . $order['order']['order_id'] . ',账号:' . $order['order']['lazada_account_id'] . '__' . date('Y-m-d H:i:s', time());
                Cache::handler()->hSet('hash:lazada_order:add_error', $error_msg);
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
        return $this->hasMany('lazada_order_detail', 'lazada_order_id', 'id')->field(true);
    }

    /**
     *
     *  linpeng
     * @
     */
    /**
     * 做数据修复使用，没有其他地方可以用到，后续可以修改，修改后请改备注
     * @return \think\model\relation\HasMany
     * linpeng
     * @2019/4/2 15:28
     */
    public function detailList()
    {
        return $this->hasMany('lazada_order_detail', 'oid', 'id')->field('oid,shipping_type');
    }
}