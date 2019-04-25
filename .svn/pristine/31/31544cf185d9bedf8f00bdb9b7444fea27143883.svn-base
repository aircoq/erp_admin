<?php
namespace app\common\model\ebay;

use app\common\cache\Cache;
use app\order\service\EbaySettlementService;
use think\Db;
use app\common\service\UniqueQueuer;
use app\order\queue\WriteEbayOrderLastShipTime;
use app\common\model\ebay\EbayListing as EbayListingModel;
use app\common\model\ebay\EbayOrderDetail as EbayOrderDetailModel;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;
use think\Exception;
use app\common\service\ChannelAccountConst;
use app\customerservice\service\MsgRuleHelp;
use app\order\service\EbayOrderService;
use app\common\model\Order;

class EbayOrder extends ErpModel
{
    use ModelFilter;

    private $filterAccount = [];
    /**
     * 帐号过滤
     * @param Query $query
     * @param $params
     */
    public function scopeEbayAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params[0], $this->filterAccount);
        if(!empty($this->filterAccount))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
        }
    }

    /**
     * 部门过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($this->filterAccount))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
        }
    }

    static $ORDER_STATUS = [
        0 => 'Completed',
        1 => 'Active',
        2 => 'Cancelled',//取消
        3 => 'CancelPending',//取消中
        4 => 'Inactive'//未付款
    ]
    // 5 => 'Shipped'
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
        $this->query('set names utf8mb4');

    }

    /**
     * 新增订单
     * @param array $data
     * [description]
     */
    public function add($data)
    {
        if (empty($data)) {
            return;
        }

        $masterTable = "ebay_order";
        $detailTable = "ebay_order_detail";
        $partitionCache = Cache::store('Partition');
        $shippingData = [];
        $detailModel = new EbayOrderDetailModel();
        foreach ($data as $key => $order) {
            // time_partition($masterTable, $order['order']['created_time']);
            if (!$partitionCache->getPartition('EbayOrder', $order['order']['created_time'])) {
                $partitionCache->setPartition('EbayOrder', $order['order']['created_time']);
            }
            if (isset($order['order']['order_id'])) { // 启动事务
                $time = time();
                $order['order']['update_time'] = $time;
                Db::startTrans();
                try {
                    $itemIds = [];
                    if (!$order['order']['id']) {
                        $hasOrder = Cache::store("EbayOrder")->hasOrder($order['order']['order_id']);
                        if($hasOrder)    //缓存里面存在，数据库没有，其他进程在处理此订单了
                        {
                            Db::commit();
                            continue;
                        }
                        $order['order']['wirte_time'] = $time;
                        $id = $this->insertGetId($order['order']);

                        /*
                         * 触发买家下单事件
                         */
                        $event_name = 'F1';
                        $order_data = [
                            'channel_id'=>ChannelAccountConst::channel_ebay,//Y 渠道id
                            'account_id'=>$order['order']['account_id'],//Y 账号id
                            'channel_order_number'=>$order['order']['order_id'],//Y 渠道订单号
                            'receiver'=>$order['order']['buyer_user_id'], //comment_user
                        ];
                        (new MsgRuleHelp())->triggerEvent($event_name, $order_data);


                        if ($id && !empty($order['orderDetail'])) {
                            foreach ($order['orderDetail'] as $detail) {
                                $itemIds[] = $detail['item_id'];
                                $detail['oid'] = $id;
                                $detailModel->insert($detail);
                                //用交易号作为key存到缓存
                                //if (param($detail, 'transaction_id')) {
                                //    $info_detail = [
                                //        'id' => $id,
                                //        'order_id' => $detail['order_id']
                                //    ];
                                //    Cache::store('EbayOrder')->orderByTransid($detail['transaction_id'], $info_detail);
                                //}
                            }
                        }
                    } else {
                        $id = $order['order']['id'];
                        
                        /*
                         * 检查平台订单状态，发生异常，拦截系统订单
                         * wangwei 2019-1-22 15:09:34
                         */
                        (new EbayOrderService())->checkStatusChangeOrder($order['order']);
                        
                        // 更新
                        $this->update($order['order'], ['id' => $id]);
                        foreach ($order['orderDetail'] as $detail) {
                            $detailModel = new EbayOrderDetailModel();
                            $row = $detailModel->where(['oid' => $id, 'record_number' => $detail['record_number']])->field('id')->find();
                            // $flag = $detailModel->update($detail, ['oid' => $id, 'transaction_id' => $detail['transaction_id'], 'sku' => $detail['sku']]);
                            if ($row) {
                                $detailModel->update($detail, ['id' => $row['id']]);
                            } else {
                                $detail['oid'] = $id;
                                $detailModel->insert($detail);
                            }
                            $itemIds[] = $detail['item_id'];
                        }
                    }

                    //插入ebay_settlement表
                    $settleOrder = $order['order'];
                    $settleOrder['site'] = isset($order['orderDetail'][0]['site'])?$order['orderDetail'][0]['site']:'';
                    $settlementSevice = new EbaySettlementService();
                    $settlementSevice->updateSettlement($settleOrder);

                    Db::commit();
                    $info = [
                        'last_update_time' => $order['order']['last_modified_time'],
                        'id' => $id
                    ];
                    // Cache::store('EbayOrder')->orderUpdateTime($order['order']['account_id'], $order['order']['order_id'], $info);
                    $shippingData[] =['id' => $id, 'itemIds' => $itemIds];
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->handleShipping($shippingData);
                    Cache::handler()->hSet('hash:EbayOrderFailure', $order['order']['order_id'], $e->getMessage());
                    // throw new Exception('MSG:'. $e->getMessage(). ' ;FILE:'. $e->getFile(). ' '. $e->getLine());
                    // return false;
                }
            }
        }

         $this->handleShipping($shippingData);
        return true;
    }

    public function handleShipping($shippingData)
    {
        if (empty($shippingData) || !is_array($shippingData)) {
            return;
        }
        $queue = new UniqueQueuer(WriteEbayOrderLastShipTime::class);
        foreach ($shippingData as $data) {
            $queue->push($data);
        }
    }
    
    public function updateLastShipTime($orderitem) {
        $order = $this->where('id', $orderitem['id'])->find();
        if(!$order) {
            return;
        }
        // 从listing中获取最迟发货时间
        $listModel = new EbayListingModel();
        $listing = $listModel->where(['item_id' => ['in', $orderitem['itemIds']]])->field('id,min(dispatch_max_time) mindate')->find();

        $upload_time = !empty($listing['mindate']) ? 86400 * intval($listing['mindate']) : 86400 * 2;
        $payment_time = empty($order['paid_time'])? $order['created_time'] : $order['paid_time'];
        $this->update(['latest_ship_time' => $payment_time + $upload_time], ['id' => $orderitem['id'], 'created_time' => $order['created_time']]);
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
        if (! empty($result)) {
            return true;
        }
        return false;
    }

    public function detail()
    {
        return parent::hasMany('EbayOrderDetail', 'order_id');
    }
}