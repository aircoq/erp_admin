<?php
namespace app\common\model\amazon;

use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;
use app\order\service\AmazonOrderService;
use app\common\service\UniqueQueuer;
use app\order\queue\AmazonOrderDetailQueue;

class AmazonOrder extends ErpModel
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

    /**
     * 关系
     * @return [type] [description]
     */
    public function role()
    {
        //一对一的关系，一个订单对应一个商品
        return $this->belongsTo('WishPlatformOnlineGoods');
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

        $masterTable = "amazon_order";
        $detailTable = "amazon_order_detail";
        $partitionCache = Cache::store('Partition');
        foreach ($data as $order) {
            Db::startTrans();
            try {
                if ($order['order']['id']) {
                    $id = $order['order']['id'];
                    
                    /*
                     * 检查订单是否需要拉入的人工审核
                     * wangwei 2019-1-22 17:08:07
                     */
                    (new AmazonOrderService())->checkStatusChangeOrder($order['order']);

                    $this->where(['id' => $id])->update($order['order']);

                    foreach ($order['orderDetail'] as $detail) {
                        // $row = Db::name($detailTable)->where(['amazon_order_id' => $id, 'record_number' => $detail['record_number']])->update($detail);
                        $row = Db::name($detailTable)->where(['amazon_order_id' => $id, 'record_number' => $detail['record_number']])->field('id')->find();
                        if ($row) {
                            Db::name($detailTable)->where(['id' => $row['id']])->update($detail);
                        } else {
                            $detail['amazon_order_id'] = $id;
                            Db::name($detailTable)->insert($detail);
                        }
                    }
                }else {
                    if (!$partitionCache->getPartition('AmazonOrder', $order['order']['created_time'])) {
                        $partitionCache->setPartition('AmazonOrder', $order['order']['created_time']);
                    }
                    unset($order['order']['id']);

                    $id = Db::name($masterTable)->insert($order['order'], false, true);
                    foreach ($order['orderDetail'] as $detail) {
                        $detail['amazon_order_id'] = $id;
                        Db::name($detailTable)->insert($detail);
                    }
                }

                $order['order']['payment_amount'] = $order['order']['actual_total'];
                (new \app\order\service\AmazonSettlementReport())->updateSettlement($order['order']);

                Db::commit();
                
                /*
                 * 触发最晚预计到达时间事件
                 */
                if(!empty($order['order']['lastest_delivery_time'])){
                    (new AmazonOrderService())->trigger_lastest_delivery_event($order['order']);
                }
                
                $info = [
                    'last_update_time' => $order['order']['last_update_time'],
                    'id'  => $id,
                    'has_detail'=>$order['order']['has_detail'],
                ];
                Cache::store('AmazonOrder')->orderUpdateTime($order['order']['account_id'], $order['order']['order_number'], $info);
            } catch (Exception $ex) {
                Db::rollback();
                Cache::handler()->hSet('hash:amazon_order:add_error', $order['order']['order_number'] . ' ' . date('Y-m-d H:i:s', time()), 'amazon订单添加异常'. $ex->getMessage());       
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
        return $this->hasMany('amazon_order_detail', 'amazon_order_id', 'id')->field(true);
    }
    
    /**
     * @desc 保存通过api拉取的亚马逊订单头数据
     * @author wangwei
     * @date 2019-3-11 12:01:45
     * @param array $orders 接口拉取的订单数据，二维数组
     * @param int $account_id 平台账号id
     * @param string $site 订单所属站点
     */
    public function saveAmazonOrdersByApi($orders, $account_id, $site){
        $return = [
            'ask'=>0,
            'message'=>'saveAmazonOrdersByApi error',
            'count'=>0
        ];
        
        /**
         * 1、简单检验
         */
        if(!isNumericArray($orders)){
            $return['message'] = 'orders not Numeric Array';
            return $return;
        }
        if(empty($account_id)){
            $return['message'] = 'account_id not empty';
            return $return;
        }
        if(empty($site)){
            $return['message'] = 'site not empty';
            return $return;
        }
        
        /**
         * 2、组装、保存订单头数据
         */
        //所有国家信息
        $countryList = Cache::store('country')->getCountry();
        $partitionCache = Cache::store('Partition');
        $count = 0;
        foreach ($orders as $order){
            
            try {
                
                /*
                 * 1、组装订单数据
                 */
                //平台订单号
                $amazonOrderId = $order['AmazonOrderId'];
                
                //查询订单是否存在
                $hasOrder = Cache::store('AmazonOrder')->orderUpdateTime($account_id, $amazonOrderId);
                if(!$hasOrder){
                    $hasOrder = AmazonOrder::where(['order_number' => $amazonOrderId,'account_id'=>$account_id])->field('last_update_time,has_detail,id')->find();
                }else{
                    //历史数据，认为有明细
                    isset($hasOrder['has_detail']) || ($hasOrder['has_detail'] = 1);
                }
                
                $last_update_time = strtotime($order['LastUpdateDate']);
                
//                 //订单已存在，且更新时间没变化，不更新订单
//                 if ($hasOrder && $hasOrder['last_update_time'] == $last_update_time) {
//                     continue;
//                 }

                //地址信息
                $shippingAddress = param($order, 'ShippingAddress',[]);
                
                $street1 = param($shippingAddress, 'AddressLine1', '');
                $street2 = param($shippingAddress, 'AddressLine2', '');
                $street3 = param($shippingAddress, 'AddressLine3', '');
                if (empty($street1) && !empty($street2)) {
                    $street1 = $street2;
                    $street2 = '';
                }
                
                //根据国家简码返回国家全英文名
                $country_code = param($shippingAddress, 'CountryCode', '');
                $country_name = ($country_code && isset($countryList[$country_code]['country_en_name'])) ? $countryList[$country_code]['country_en_name'] : '';
                
                $county = param($shippingAddress, 'County', '');
                $district = param($shippingAddress, 'District', '');
                $district  = $county ? $county : $district;
                
                $orderTotal = param($order, 'OrderTotal', []);
                
                //订单表数据
                $ao_row = [
                    'order_number'=>$amazonOrderId,
                    'site'=>$site,
                    'payment_method'=>param($order,'PaymentMethod',''),
                    'currency'=>param($orderTotal,'CurrencyCode',''),
                    'account_id'=>$account_id,
                    'payment_time'=>param($order,'PurchaseDate',0) ? strtotime($order['PurchaseDate']) : 0,
                    'actual_total'=>param($orderTotal,'Amount',0),
                    'transport_id'=>0, //数据库字段不能为空，先置为0
                    'actual_shipping'=>0,//暂时为0，拉取明细时更新
                    'latest_ship_time'=>param($order,'LatestShipDate',0) ? strtotime($order['LatestShipDate']) : 0,
                    'earliest_ship_time'=>param($order,'EarliestShipDate',0) ? strtotime($order['EarliestShipDate']) : 0,
                    'or_transport'=>param($order, 'ShipServiceLevel',''),
                    'order_status'=>$order['OrderStatus'],
                    'created_time'=>strtotime($order['PurchaseDate']),
                    'last_update_time'=>$last_update_time,
                    'declared_price'=>0.00,
                    'fulfillment_channel'=>param($order, 'FulfillmentChannel', ''),
                    'sales_channel'=>param($order, 'SalesChannel', ''),
                    'ship_service_level'=>param($order, 'ShipServiceLevel', ''),
                    'marketplace_id'=>param($order, 'MarketplaceId', ''),
                    'shipment_serviceLevel_category'=>$order['ShipmentServiceLevelCategory'],
                    'user_name'=>param($shippingAddress, 'Name', ''),
                    'platform_username'=>param($order, 'BuyerName', ''),
                    'email'=>param($order, 'BuyerEmail', ''),
                    'country_name'=>$country_name,
                    'country'=>$country_code,
                    'state'=>param($shippingAddress, 'StateOrRegion', ''),
                    'city'=>param($shippingAddress, 'City', ''),
                    'district'=>$district,
                    'address1'=>$street1,
                    'address2'=>$street2,
                    'address3'=>$street3,
                    'phone'=>param($shippingAddress, 'Phone', ''),
                    'zip_code'=>param($shippingAddress, 'PostalCode', ''),
                    'unshipped_numbers'=>param($order, 'NumberOfItemsUnshipped', ''),
                    'shipped_numbers'=>param($order, 'NumberOfItemsShipped',0),
                    'earliest_delivery_time' => isset($order['EarliestDeliveryDate']) ? strtotime($order['EarliestDeliveryDate']) : 0,
                    'lastest_delivery_time'  => isset($order['LatestDeliveryDate']) ? strtotime($order['LatestDeliveryDate']) : 0,
                    'has_detail'=>0//认为没有明细，重新拉取一次明细
                ];
                
                /*
                 * 2、添加\更新表数据
                 */
                $aoModel = new AmazonOrder();
                $id = param($hasOrder, 'id', 0);
                if($id){
                    $aoModel->where('id',$id)->update($ao_row);
                }else{
                    if(!$partitionCache->getPartition('AmazonOrder', $ao_row['created_time'])){
                        $partitionCache->setPartition('AmazonOrder', $ao_row['created_time']);
                    }
                    $id = $aoModel->insertGetId($ao_row);
                }
                
                /*
                 * 3、插入拉取订单明细队列
                 */
                if(!$ao_row['has_detail']){
                    $data = [
                        'id'=>(int)$account_id,
                        'order_id'=>$amazonOrderId,
                    ];
                    (new UniqueQueuer(AmazonOrderDetailQueue::class))->push(json_encode($data));
                }
                
                /*
                 * 4、其他业务处理
                 */
                //检查订单是否需要拉入的人工审核
                if($id && $ao_row['has_detail'] == 1){
                    (new AmazonOrderService())->checkStatusChangeOrder($ao_row);
                }
                //更新amazon_settlement数据
                $ao_row['payment_amount'] = $ao_row['actual_total'];
                (new \app\order\service\AmazonSettlementReport())->updateSettlement($ao_row);
                
                //触发最晚预计到达时间事件
                if(!empty($ao_row['lastest_delivery_time'])){
                    (new AmazonOrderService())->trigger_lastest_delivery_event($ao_row);
                }
                
                //存入缓存
                $orderCache = [
                    'last_update_time' => $ao_row['last_update_time'],
                    'id'  => $id,
                    'has_detail'=>$ao_row['has_detail']
                ];
                Cache::store('AmazonOrder')->orderUpdateTime($ao_row['account_id'], $ao_row['order_number'], $orderCache);
                
                //执行数量累加
                $count++;
                
            } catch (\Exception $e) {
                $error_msg = 'msg:' . $e->getMessage() . ',code:' . $e->getCode() . ',file:' . $e->getFile() . ',line:' . $e->getLine();
                Cache::handler()->hSet('hash:amazon_order:add_error', $ao_row['order_number'] . ' ' . date('Y-m-d H:i:s', time()), 'amazon订单添加异常:'. $error_msg);
                throw new \Exception($error_msg);
            }
        }
        
        /**
         * 3、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['count'] = $count;
        return $return;
    }
    
    /**
     * @desc 保存通过api拉取的亚马逊订单明细数据
     * @author wangwei
     * @date 2019-3-29 15:30:52
     * @param array $items
     * @param string $account_id
     * @param string $amazon_order_number
     */
    public function saveAmazonOrderItemsByApi($items, $account_id, $amazon_order_number){
        $return = [
            'ask'=>0,
            'message'=>'saveAmazonOrderItemsByApi error',
            'add_count'=>0,
            'up_count'=>0
        ];
        
        /**
         * 1、简单检验
         */
        if(!isNumericArray($items)){
            $return['message'] = 'items not Numeric Array';
            return $return;
        }
        if(empty($account_id)){
            $return['message'] = 'account_id not empty';
            return $return;
        }
        if(empty($amazon_order_number)){
            $return['message'] = 'amazon_order_number not empty';
            return $return;
        }
        $ao_where = [
            'order_number'=>$amazon_order_number,
            'account_id'=>$account_id
        ];
        $aoModel = new AmazonOrder();
        $aoHas = $aoModel->where($ao_where)->field('id,sales_channel,order_status,last_update_time')->find();
        if(empty($aoHas)){
            $return['ask'] = 1;
            $return['message'] = 'AmazonOrder not exist';
            return $return;
        }
        
        /**
         * 2、组装、保存订单明细数据
         */
        try {
            Db::startTrans();
            
            $total_shipping_fee = 0;
            $add_count = $up_count = 0;
            //1、循环处理明细表数据
            foreach ($items as $item) {
                //数组取值
                $itemPrice = param($item, 'ItemPrice', []);
                $shippingPrice = param($item, 'ShippingPrice', []);
                $shippingDiscount = param($item, 'ShippingDiscount', []);
                $promotionDiscount = param($item, 'PromotionDiscount', []);
                $shippingTax = param($item, 'ShippingTax', []);
                $conditionId = param($item, 'ConditionId', []);
                
                $tmp_fee = floatval(param($shippingPrice, 'Amount', 0));
                $total_shipping_fee += $tmp_fee;
                $aod_row = [
                    'amazon_order_id'=>$aoHas['id'],
                    'record_number'=>$item['OrderItemId'],
                    'order_number'=>$amazon_order_number,
                    'item_price'=>param($item, 'QuantityOrdered', 0) ? round(param($itemPrice, 'Amount', 0) / $item['QuantityOrdered'], 4) : 0,
                    'currency_code'=>param($itemPrice, 'CurrencyCode', ''),
                    'online_sku'=>$item['SellerSKU'],
                    'sku'=>$item['SellerSKU'],
                    'qty'=>$item['QuantityOrdered'],
                    'shipping_fee'=>$tmp_fee,
                    'item_id'=>$item['ASIN'],
                    'item_title'=>param($item,'Title',''),
                    'item_url'=>"https://www.{$aoHas['sales_channel']}/gp/product/{$item['ASIN']}",
                    'promotion_discount'=>param($promotionDiscount, 'Amount', 0),
                    'shipping_tax'=>param($shippingTax, 'Amount', 0),
                    'shipping_discount'=>param($shippingDiscount, 'Amount', 0),
                    'shipping_price'=>$tmp_fee,
                    'condition_note'=>param($item, 'ConditionNote', ''),
                    'condition_subtype_id'=>param($item, 'ConditionSubtypeId', ''),
                    'condition_id'=>param($conditionId, 'FieldValue',''),
                    'update_time'=>time(),
                ];
                $aodModel = new AmazonOrderDetail();
                $aod_where = [
                    'record_number'=>$aod_row['record_number'],
                ];
                $aodHas = $aodModel->where($aod_where)->field('id,order_number')->find();
                if($aodHas){
                    /*
                     * 坑：亚马逊fba订单两个明细过段时间会被自动拆分成，两个相同订单号不同账号的订单，
                     * 遇到这种情况直接更新明细避免插入时record_number唯一索引报错
                     * 
                     * 以防万一，已有订单号不等于当前订单号，异常抛出
                     * wangwei 2019-4-8 11:56:31
                     */
                    if($aodHas['order_number'] != $amazon_order_number){
                        throw new Exception("error order_number:{$amazon_order_number},has order_number:{$aodHas['order_number']}");
                    }
                    $aodModel->where('id',$aodHas['id'])->update($aod_row);
                    $up_count++;
                }else{
                    $aod_row['created_time'] = time();
                    $aodModel->insertGetId($aod_row);
                    $add_count++;
                }
            }
            //2、更新主表数据
            $has_detail = 1;
            $ao_up = [
                'actual_shipping'=>$total_shipping_fee,
                'has_detail'=>$has_detail
            ];
            $aoModel->where('id',$aoHas['id'])->update($ao_up);
            //3、更新缓存
            $orderCache = [
                'last_update_time' => $aoHas['last_update_time'],
                'id'  => $aoHas['id'],
                'has_detail'=>$has_detail
            ];
            Cache::store('AmazonOrder')->orderUpdateTime($account_id, $amazon_order_number, $orderCache);
            
            $return['ask'] = 1;
            $return['message'] = 'success';
            $return['add_count'] = $add_count;
            $return['up_count'] = $up_count;
            
            Db::commit();
            
        } catch (Exception $e) {
            Db::rollback();
            
            $return['message'] = $e->getMessage();
        }
        
        return $return;
    }

}