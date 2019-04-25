<?php
namespace app\carrier\service\operation;

use app\carrier\service\ShippingMethodBase;
use app\common\model\Warehouse as WarehouseModel;
use app\common\model\ShippingMethod as ShippingMethodModel;

/**
 * Class Winit
 * @package app\carrier\service\operation
 */
class Winit extends ShippingMethodBase
{
    private $config = [
        'sandbox'       => true,
        'token'         => '89435277FA3BA272DE795559998E-',
        'app_key'       => 'qiongjierui@163.com',
        'sandboxUrl'    => 'http://erp.sandbox.winit.com.cn/ADInterface/api',
        'productionUrl' => 'http://api.winit.com.cn/ADInterface/api',
    ];
    
    private $post_data = [
        'action'      => '',
        'app_key'     => '',
        'data'        => '',            
        'format'      => 'json',
        'platform'    => 'SELLERERP',
        'sign_method' => 'md5',
        'timestamp'   => '',
        'version'     => "1.0"
    ];
    
    /**
     * 获取提交数据
     * 
     * @param string $action
     * @param string $data
     * @return array
     */
    private function getPostData($action, $data)
    {
        $this->post_data['app_key']   = $this->config['app_key'];
        $this->post_data['timestamp'] = date('Y-m-d H:i:s');
        $this->post_data['action']    = $action;
        $this->post_data['data']      = $data;
        $this->post_data['sign']      = $this->getSign($this->post_data, $this->config['token']);
        return $this->post_data;
    }
    
    // private $token = '89435277FA3BA272DE795559998E-';   
    // private $app_key = 'qiongjierui@163.com';
    // private $token = 'F8F1F366C5B1B276B1EAEBC6496D8E71';
    // private $app_key = 'rondaful@rondaful.com';
    
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function getToken()
    {         
        $getTokenData = [
            'app_key' => $this->app_key,
            'action'  => 'getToken'
        ];        
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $data = new winit\GetTokenDataType(['userName' => 'qiongjierui@163.com', 'passWord' => '888']);
        
        $getTokenData['data'] = $data;
        
        $getToken = new winit\GetTokenRequestType($getTokenData);
        
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getToken($getToken);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['msg'] = $result->msg;
        }
        
        return json($json);
    }
    
    /**
     * 获取Winit产品分类
     * @param string categoryID as geting categories parentId
     * @return array
     */
    public function getCategoryInfo($categoryID = 0) 
    {   
        $json      = ['code' => 200, 'msg' => '', 'data' => ''];       
        $post_data = $this->getPostData('getProductCategoryInfo', json_decode(['categoryID' => $categoryID]));        
        $result    = $this->callOperation($post_data);    
        if ($result->code == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return json($json);
    }
    
    /**
     * 获取仓库信息
     */
    public function getWarehouse()
    {   
        $json      = ['code' => 200, 'msg' => '', 'data' => ''];       
        $post_data = $this->getPostData('queryWarehouse', '{}');     
        $result    = $this->callOperation($post_data);        
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }        
        return $json;
    }
    
    /**
     * 获取运输方式信息
     * 
     * @param int $warehouse_code
     * @return array
     */
    public function getDeliveryWay($warehouse_code)
    {
        $json      = ['code' => 200, 'msg' => '', 'data' => '']; 
        $post_data = $this->getPostData('queryDeliveryWay', json_encode(['warehouseID' => intval($warehouse_code)]));
        $result    = $this->callOperation($post_data);       
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return $json;
    }
    
    /**
     * 获取仓库库存
     * 
     * @param array $params
     * @return array
     */
    public function getInventory($params = null)
    {
        $json      = ['code' => 200, 'msg' => '', 'data' => ''];        
        $data      = ['warehouseID' => 1005189, 'pageSize'=> 200, 'pageNum' => 1];
        $post_data = $this->getPostData('queryWarehouseStorage', json_encode($data));
        $result = $this->callOperation($post_data);
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return json_encode($json);
    }
    
    /**
     * 查询商品单品信息
     * 
     * @param string $itemBarcode
     * @return array
     */
    public function getItemInformation($itemBarcode)
    {   
        $json      = ['code' => 200, 'msg' => '', 'data' => ''];
        $data      = ['itemBarcode'=>'S010000000000001392'];
        $post_data = $this->getPostData('getItemInformation', json_encode($data));        
        $result    = $this->callOperation($post_data);        
        if ($result['code'] == 0) {
            $json['data']   = $result['data'];
        } else {
            $json['code']   = 400;
            $json['msg']    = $result['msg'];
        }
        
        return json($json);
    }
    
    /**
     * 生成签名
     * 
     * @param array consist of generating sgin parameter
     * @param string token
     * @return string the string is winit sign
     */
    private function getSign($info, $token)
    {
        $str  = $token;        
        foreach($info as $key=>$value) {
            $str .= $key . $value;
        }        
        $str .= $token;        
        $sign = $info['sign_method']($str);        
        return strtoupper($sign);
    }
    
    /**
     * 创建出库单
     * 
     * @param array $order
     * @param string $action
     * @return array
     */
    private function createOutbound($order, $action)
    {
        $json = [
            'code' => 400, 
            'msg'  => '', 
            'data' => '' 
        ];       
        do {
            $order_info = $this->formatPackageInfo($order);            
            if ($order_info['errorCode'] == 1) {
                $json['msg'] = $order_info['errorMsg'];
                break;
            }            
            $post_data = $this->getPostData($action, json_encode($order_info['data']));            
            $result    = $this->callOperation($post_data);           
            if ($result['code'] == 0) {
                $json['code'] = 200;
                $json['data'] = $result['data']['outboundOrderNum'];
            } else {
                $json['msg']  = $result['msg'];
            }        
        } while(false);
        
        return $json;
    }
    
    /**
     * 创建出库单(草稿中)
     * 
     * @param int $order_id
     * @int array
     */
    public function createOutboundInfo($order_id)
    {
        $json = $this->createOutbound($order_id, 'createOutboundInfo');        
        return $json;
    }
    
    /**
     * 创建出库单(确认状态)
     * 
     * @param int $order_id
     * @return array
     */
    public function createOutboundOrder($order_id)
    {
        $json = $this->createOutbound($order_id, 'createOutboundOrder');        
        return $json;
    }
    
    /**
     * 提交/作废海外出库单（当出库单为草稿状态中时,用户可通过API确认/作废出库单
     * 
     * @param string $outbound_order_num 出库单号
     * @param string $action
     * @return array
     */
    private function manageOutboundOrder($outbound_order_num, $action)
    {   
        $json = ['code' => 200, 'msg'  => '', 'data' => ''];       
        if (empty($outbound_order_num)) {
            $json['code'] = 400;
            $json['msg']  = '出库单号不能为空!!!';            
            return $json;
        }       
        $post_data = json_encode($action, ['outboundOrderNum' => $outbound_order_num]);        
        $result = $this->callOperation($post_data);        
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return $json;
    }
    
    /**
     * 当出库单处于草稿状态时,可用此接口作废
     * 
     * @param string $order_code
     * @return array
     */
    public function voidOutboundOrder($order_code = '')
    {
        $json = $this->manageOutboundOrder($order_code, 'voidOutboundOrder');       
        return $json;
    }
    
    /**
     * 当出库单处于草稿状态时,可用此接口确认为出库中
     * 
     * @param
     */
    public function confirmOutboundOrder($order_code = '')
    {
        $json = $this->manageOutboundOrder($order_code, 'confirmOutboundOrder');       
        return $json;
    }
    
    
    /**
     * 当出库单处于草稿状态时，可用此接口修改出库单
     * 
     * @param int $order_id
     * @return array
     */
    public function updateOutboundOrder($order_id = 0)
    {
        $json       = ['code' => 200, 'msg'  => '', 'data' => ''];
        $order_info = $this->getOrderByOrderId($order_id);        
        if (empty($order_info)) {
            $json['code'] = 400;
            $json['msg']  = '订单信息不能为空!!!';        
            return $json;
        }      
        $post_data = $this->getPostData('updateOutboundOrder', json_encode($order_info['data']));        
        $result    = $this->callOperation($post_data);        
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return $json;
    }
    
    /**
     * 查询出库单详情
     * 
     * @param array $params search
     * @return array
     */
    public function queryOutboundOrder($params = null)
    {
        $json  = ['code' => 200, 'msg'  => '', 'data' => ''];
        $data  = [];       
        $exist = false;       
        if (isset($params['order_code']) && intval($params['order_code'])) {
            $data['outboundOrderNum'] = intval($params['order_code']);
            $exist = true;
        }        
        if (isset($params['start_date']) && $params['start_date']) {
            $data['startDate'] = $params['start_date'];
        } elseif (!$exist) {
            $data['startDate'] = date('Y-m-d', strtotime("-1 day"));
        }       
        if (isset($params['end_date']) && $params['end_date']) {
            $data['endDate'] = $params['end_date'];
        } elseif (!$exist) {
            $data['endDate'] = date('Y-m-d', strtotime("-1 day"));
        }        
        if (isset($params['page_size']) && intval($params['page_size'])) {
            $data['pageSize'] = intval($params['page_size']);
        } else {
            $data['pageSize'] = 200;
        }       
        if (isset($params['page_num']) && intval($params['page_num'])) {
            $data['pageNum'] = intval($params['page_num']);
        } else {
            $data['pageNum'] = 1;
        }
        $post_data = $this->getPostData('queryOutboundOrder', json_encode($data));
        $result = $this->callOperation($post_data);        
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return $json; 
    }
    
    /**
     * 查询出库单列表
     * 
     * @param array $params
     * @return array
     */
    public function queryOutboundOrderList($params = null)
    {
        $json = ['code' => 200, 'msg'  => '', 'data' => ''];
        $warehouseId = 1005189;       
        if (empty($warehouseId)) {
            $json['code'] = 400;
            $json['msg'] = '出库单号不能为空!!!';            
            return json($json);
        }
        $data['warehouseId'] = $warehouseId;
        
        if (isset($params['outbound_order_num']) && $params['outbound_order_num']) {
            $data['outboundOrderNum'] = $params['outbound_order_num'];
        }
        
        if (isset($params['seller_order_no']) && $params['seller_order_no']) {
            $data['sellerOrderNo'] = $params['seller_order_no'];
        }
        
        if (isset($params['tracking_no']) && $params['tracking_no']) {
            $data['trackingNo'] = $params['tracking_no'];
        }

        if (isset($params['receiver_name']) && $params['receiver_name']) {
            $data['receiverName'] = $params['receiver_name'];
        }
        
        if (isset($params['booking_operator']) && $params['booking_operator']) {
            $data['bookingOperator'] = $params['booking_operator'];
        }
        
        if (isset($params['product_name']) && $params['product_name']) {
            $data['productName'] = $params['product_name'];
        }
        
        if (isset($params['product_value']) && $params['product_value']) {
            $data['productValue'] = $params['product_value'];
        }
        
        if (isset($params['product_sku']) && $params['product_sku']) {
            $data['productSku'] = $params['product_sku'];
        }
        
        if (isset($params['share_order_type']) && $params['share_order_type']) {
            $data['shareOrderType'] = $params['share_order_type'];
        } else {
            // $dataType->shareOrderType = 1;
        }
        
        if (isset($params['date_ordered_start_date']) && $params['date_ordered_start_date']) {
            $data['dateOrderedStartDate'] = $params['date_ordered_start_date'];
        } else {
            $data['dateOrderedStartDate'] = date('Y-m-d', strtotime("-10 day"));
        }
        
        if (isset($params['date_ordered_end_date']) && $params['date_ordered_end_date']) {
            $data['dateOrderedEndDate'] = $params['date_ordered_end_date'];
        } else {
            $data['dateOrderedEndDate'] = date('Y-m-d');
        }
        
        if (isset($params['status']) && $params['status']) {
            $data['status'] = $params['status'];
        } else {
            $data['status'] = 'valid';
        }
        
        if (isset($params['page_size']) && intval($params['page_size'])) {
            $data['pageSize'] = intval($params['page_size']);
        } else {
            $data['pageSize'] = 100;
        }
        
        if (isset($params['page_num']) && intval($params['page_num'])) {
            $data['pageNum'] = intval($params['page_num']);
        } else {
            $data['pageNum'] = 1;
        }
        
        $post_data = $this->getPostData('queryOutboundOrderList', json_encode($data));
        $result    = $this->callOperation($post_data);
        if ($result['code'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['msg'];
        }
        
        return json($json);
    }
    
    /**
     * 添加万邑通物流系统物流方式
     */
    public function insertShippingMethod()
    {
        $warehouse_winit = [
            ['code' => '1000001', 'name' => 'AU Warehouse(澳洲仓)'],
            ['code' => '1000008', 'name' => 'USWC warehouse(美国仓)'],
            ['code' => '1000069', 'name' => 'UK Warehouse (英国仓)'],
            ['code' => '1000089', 'name' => 'DE Warehouse (德国仓)'],
            ['code' => '1000129', 'name' => 'USKY warehouse'],
            ['code' => '1005189', 'name' => 'UKMA Warehouse'],
            ['code' => '1008190', 'name' => 'USKYN Warehouse'],
            ['code' => '1012190', 'name' => 'BEMO Warehouse'],
            ['code' => '1012191', 'name' => 'US Portlan warehouse'],
            ['code' => '1017191', 'name' => 'UK Oldham']
        ];
        
        $records = [];
        $sysShipMethodModel = new \app\common\model\SystemShippingMethod();
        
        foreach ($warehouse_winit as $warehouse) {
            $object = $this->getDeliveryWay($warehouse['code']);
            if ($object['code'] === 200 && count($object['data'])) {
                foreach ($object['data'] as $list) {
                    if (!$sysShipMethodModel->check(['warehouse_code' => $warehouse['code'], 'carrier' => 'winit', 'code' => $list->deliveryID])) {
                        $records[] = [
                            'shortname' => $list->deliveryWay,
                            'code'      => $list->deliveryID,
                            'fullname'  => $list->deliveryWay,
                            'type'      => 1,
                            'has_tracking_number' => 1,
                            'access_tracking_number_method' => 0,
                            'warehouse_code' => $warehouse['code'],
                            'carrier'   => 'winit'
                        ];
                    }
                }
            }           
        }
        
        $sysShipMethodModel->saveAll($records);
            
        return json(['messgae' => '加载成功']);
    }
    
    /**
     * 更新库存
     */
    public function updateInventory()
    {
        $inventoryInfo = $this->getInventory();
        $object = json_decode($inventoryInfo);
        $warehouse_id = 4;
        
        foreach ($object->data->list as $key => $list) {
            if (0 === strpos($list->productCode, 'RMEU')) {
                continue;               
            }
            $warehouseGoodsModel = new \app\common\model\WarehouseGoods();
            if (!$warehouseGoodsModel->check(['thirdparty_goods_sku' => $list->productCode, 'warehouse_id' => $warehouse_id])) {
                $record = [
                    'sku'                       => $list->productCode,
                    'thirdparty_goods_sku'      => $list->productCode,
                    'available_quantity'        => $list->inventory,
                    'instransit_quantity'       => $list->pipelineInventory,
                    'waiting_shipping_quantity' => $list->reservedInventory,
                    'warehouse_id'              => $warehouse_id,
                    'create_time'               => time(),
                    'update_time'               => time()
                ];
                
                $warehouseGoodsModel->allowField(true)->isUpdate(false)->save($record); 
            } else {
                $record = [
                    'available_quantity'        => $list->inventory,
                    'instransit_quantity'       => $list->pipelineInventory,
                    'waiting_shipping_quantity' => $list->reservedInventory,
                    'update_time'               => time()
                ];
                
                $warehouseGoodsModel->allowField(true)->isUpdate(true)->save($record, ['thirdparty_goods_sku' => $list->productCode, 'warehouse_id' => $warehouse_id]);
            }
        }
    }
    
    /**
     * 创建包裹信息
     * $config  配置信息
     * $package  包裹信息
     * $products 包裹中的所有产品信息
     * $channel  渠道的英文名
     * @param unknown $name
     */
    public function createOrder($config,$package)
    {
        
    }
    
    /**
     * 提交预报包裹信息
     * @param unknown $name
     */
    public function confirmOrder($config, $package)
    {
        
    }
    
    /**
     * 删除包裹信息
     * @param unknown $name
     */
    public function deleteOrder($config, $package)
    {
        
    }
    
    
    /**
     * 获取物流信息
     * @param unknown $name
     */
    public function getLogisticsServiceList($config)
    {
        
    }
    
    /**
     * 获取跟踪号
     * @param unknown $config
     * @param unknown $package
     */
    public function getTrackNumber($config,$package)
    {
        
    }
    
    /**
     * curl 操作
     * @param string $url request url
     * @param string $post_data request parameters
     * @param array  $headers An associative array of HTTP headers
     */
    private function curl($url,$post_data, $headers) 
    {
        $ch = curl_init();             
        curl_setopt($ch, CURLOPT_URL, $url);		
        curl_setopt($ch, CURLOPT_POST, 1);                 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);				
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_HEADER, 0);       
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);       
	$output = curl_exec($ch);       
	curl_close($ch);       
        return $output;
    }
    
    /**
     * 发送请求
     *
     * @param array $data 发送数据
     * @return object json decode.
     */
    private function callOperation($data)
    {
        $url = $this->getUrl(); 
        $post_data = json_encode($data);  
        $headers[] = 'Content-Type:application/json';       
        $headers[] = 'Content-Length:' . strlen($post_data);       
	$response = $this->curl($url, $post_data, $headers);       
        $result = json_decode($response, true);       
        if (!$result) {                   
            $result['code'] = 400;                      
            $result['msg']  = '系统错误!!';
        }        
        return $result;
    }

    /**
     * 组织请求url及参数
     *
     * @param string $path
     * @return string $url
     */
    private function getUrl()
    {
        $url = $this->config['sandbox'] ? $this->config['sandboxUrl'] : $this->config['productionUrl'];       
        return $url;
    }
    
    /**
     * 获取订单信息
     * @param integer order id
     * @return array order info
     */
    public function getOrderInfoByOrderId($order_id)
    {  
        $result = [
            'errorCode' => 0,
            'errorMsg' => '',
            'data' => []
        ];
        
        do {
            
            $order_info = \app\common\model\Order::get(['id' => intval($order_id)]);
        
            if (empty($order_info)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '订单不存在';
                break;
            }
            
            $warehouse_info = \app\common\model\Warehouse::get(['id' => $order_info->warehouse_id]);
        
            $sm_info = \app\common\model\ShippingMethod::get(['id' => $order_info->shipping_id]);
            
            if (empty($warehouse_info) || empty($sm_info)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '仓库或者邮寄方式不匹配';
                break;
            }
        
            $detail_info = \app\common\model\OrderDetail::all(['order_id' => $order_id]);
            
            if (empty($detail_info)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '订单不存在产品详情';
                break;
            }
            
            foreach($detail_info as $list) {
                $items[] = [
                    'productCode' => $list->channel_sku,
                    'productNum' => $list->sku_quantity,
                    'eBayBuyerID' => '',
                    'eBaySellerID' => '',
                    'eBayItemID' => intval($list->channel_item_id),
                    'specification' => ''
                ];
            }
            
            $result['data'] = [
                'warehouseID' => intval($warehouse_info->code),
                'eBayOrderID' => '',
                'deliveryWayID'   => intval($sm_info->code),
                'repeatable' => 'Y',
                'insuranceTypeID'    => 1000000, // 不买保险
                'sellerOrderNo'      => $order_info->order_number,
                'recipientName' => $order_info->consignee,
                'phoneNum' => $order_info->tel ? $order_info->tel: $order_info->mobile,
                'zipCode'  => $order_info->zipcode,
                'emailAddress' => $order_info->email,
                'state' => $order_info->country_code, // 国家编码
                'region' => $order_info->province,    // 所在的州
                'city' => $order_info->city,
                'address1' => $order_info->address,
                'address2' => '',
                'doorplateNumbers' => '',  
                'isShareOrder' => 'N',
                'productList' => $items
            ];
            
        } while(false);
        
        return $result;
    }
    
    /**
     * 组织包裹信息
     * @param array $order
     * @return array order info
     */
    public function formatPackageInfo(&$order)
    {  
        $result = ['errorCode' => 0, 'errorMsg' => '', 'data' => []];       
        do {
            if (empty($order)) {
                $result['errorCode'] = 1;
                $result['errorMsg']  = '订单不存在';
                break;
            }           
            $warehouseInfo = WarehouseModel::get(['id' => $order['warehouse_id']]);      
            $smInfo        = ShippingMethodModel::get(['id' => $order['shipping_id']]);            
            if (empty($warehouseInfo) || empty($smInfo)) {
                $result['errorCode'] = 1;
                $result['errorMsg']  = '仓库或者邮寄方式不匹配';
                break;
            }
            if (empty($order['product'])) {
                $result['errorCode'] = 1;
                $result['errorMsg']  = '订单不存在产品详情';
                break;
            }            
            foreach($order['product'] as $list) {
                $items[] = [
                    'productCode'   => $list['sku'],
                    'productNum'    => $list['qty'],
                    'eBayBuyerID'   => '',
                    'eBaySellerID'  => '',
                    'eBayItemID'    => 0, // intval($list->channel_item_id),
                    'specification' => ''
                ];
            }
            
            $result['data'] = [
                'warehouseID'      => intval($warehouseInfo['code']),
                'eBayOrderID'      => '',
                'deliveryWayID'    => intval($smInfo['code']),
                'repeatable'       => 'Y',
                'insuranceTypeID'  => 1000000, // 不买保险
                'sellerOrderNo'    => $order['number'],
                'recipientName'    => $order['sender']['sender_name'],
                'phoneNum'         => $order['sender']['sender_phone'] ? $order['sender']['sender_phone'] : $order['sender']['sender_mobile'],
                'zipCode'          => $order['sender']['sender_zipcode'],
                'emailAddress'     => $order['sender']['email'],
                'state'            => $order['sender']['sender_country'], // 国家编码
                'region'           => $order['sender']['sender_state'],    // 所在的州
                'city'             => $order['sender']['sender_city'],
                'address1'         => $order['sender']['sender_street'],
                'address2'         => '',
                'doorplateNumbers' => '',  
                'isShareOrder'     => 'N',
                'productList'      => $items
            ];
        } while(false);
        
        return $result;
    }
    
    /**
     * 获取修改出库单信息
     * @acess public
     * @param integer  $order_id 订单Id
     * @return array   
     */
    public function getOrderByOrderId($order_id)
    {
       $orderModel = new \app\common\model\Order();
       $order = $orderModel->where(['id' => $order_id])->find();
       
       $result = [];
       if (!empty($order)) {
            $result = [
               'outboundOrderNum' => intval($order->third_order_number),
               'recipientName'    => $order->consignee,
               'phoneNum'         => $order->tel ? $order->tel: $order->mobile,
               'zipCode'          => $order->zipcode,
               'emailAddress'     => $order->email,
               'state'            => $order->country_code,
               'region'           => $order->province,
               'city'             => $order->city,
               'address1'         => $order->address,
               'address2'         => ''
            ];
       }
       return $result;
    }
}
