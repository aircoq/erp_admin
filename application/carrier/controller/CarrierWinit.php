<?php
namespace app\carrier\controller;

use app\common\controller\Base;
use app\carrier\service;
use app\carrier\type\winit;
use app\carrier\model;
use think\Request;

/**
 * Class carrierwinit
 * @package app\carrier\controller
 */
class CarrierWinit extends Base
{
    private $config = [
        'sandbox' => false
    ];
    
    // private $token = '89435277FA3BA272DE795559998E-';
    
    // private $app_key = 'qiongjierui@163.com';
    private $token = 'F8F1F366C5B1B276B1EAEBC6496D8E71';
    private $app_key = 'rondaful@rondaful.com';
    
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
    public function getCategoryInfo() 
    {   
        $json = [
            'code' => 200,
            'msg' => '',
            'data' => ''
        ];
        
        $gc_data = [
            'action' => 'getProductCategoryInfo',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\GetProductCategoryInfoDataType';
        
        $data = new winit\GetProductCategoryInfoDataType(['categoryID' => 0]);
        
        $gc_data['data'] = $data->toJson();
        
        $gc_data['sign'] = $this->getSign($gc_data, $this->token);
        
        $gc_data['data'] = $data;
        
        $category = new winit\BaseRequestType($gc_data);
        
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getCategories($category);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg'] = $result->msg;
        }
        
        return json($json);
    }
    
    /**
     * 获取仓库信息
     */
    public function getWarehouse()
    {   
        $json = [
            'code' => 200,
            'msg' => '',
            'data' => ''
        ];
        
        $qw_data = [
            'action' => 'queryWarehouse',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'string';
        
        $qw_data['data'] = '{}';
        
        $qw_data['sign'] = $this->getSign($qw_data, $this->token);
        
        $qw_data['data'] = '{}';
        
        $qw_type = new winit\BaseRequestType($qw_data);
        
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getWarehouse($qw_type);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg'] = $result->msg;
        }
        
        return json($json);
    }
    
    /**
     * 获取运输方式信息
     */
    public function getDeliveryWay($warehouse_code)
    {
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $dw_data = [
            'action' => 'queryDeliveryWay',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\QueryDeliveryWayDataType';
        
        $dwd_type = new winit\QueryDeliveryWayDataType(['warehouseID' => intval($warehouse_code)]);
        
        $dw_data['data'] = $dwd_type->toJson();
        
        $dw_data['sign'] = $this->getSign($dw_data, $this->token);
        
        $dw_data['data'] = $dwd_type;
        
        $dw_type = new winit\BaseRequestType($dw_data);
        
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->queryDeliveryWay($dw_type);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
        }
        
        return $json;
    }
    
    /**
     * 获取仓库库存
     */
    public function getInventory()
    {
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $inventory_data = [
            'action' => 'queryWarehouseStorage',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\QueryWarehouseStorageDataType';
        
        $inventory_data_type = new winit\QueryWarehouseStorageDataType(['warehouseID' => 1005189, 'pageSize'=> 200, 'pageNum' => 1]);
        
        $inventory_data['data'] = $inventory_data_type->toJson();
        
        $inventory_data['sign'] = $this->getSign($inventory_data, $this->token);
        
        $inventory_data['data'] = $inventory_data_type;
        
        $inventory_type = new winit\BaseRequestType($inventory_data);
        
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->queryWarehouseStorage($inventory_type);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
        }
        
        return json_encode($json);
    }
    
    /**
     * 查询商品单品信息
     */
    public function getItemInformation()
    {   
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $item_data = [
            'action' => 'getItemInformation',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\GetItemInformationDataType';
        
        $item_data_type = new winit\GetItemInformationDataType(['itemBarcode'=>'S010000000000001392']);
        
        $item_data['data'] = $item_data_type->toJson();
        
        $item_data['sign'] = $this->getSign($item_data, $this->token);
        
        $item_data['data'] = $item_data_type;
        
        $item_type = new winit\BaseRequestType($item_data);
               
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getItemInformation($item_type);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
        }
        
        return json($json);
    }
    
    /**
     * 生成签名
     * @param array consist of generating sgin parameter
     * @param string token
     * @return string the string is winit sign
     */
    private function getSign($info, $token)
    {
        $str = $token;
        
        foreach($info as $key=>$value) {
            $str .= $key . $value;
        }
        
        $str .= $token;
        
        $sign = $info['sign_method']($str);
        
        return strtoupper($sign);
    }
    
    /**
     * 创建出库单
     */
    private function createOutbound($order_id, $action)
    {
        $json = [
            'code' => 400,
            'msg'  => '',
            'data' => ''
        ];
        
        do {
            $info_data = [
                'action' => $action,
                'app_key' => $this->app_key,
                'data' => '',            
                'format' => 'json',
                'platform' => 'SELLERERP',
                'sign_method' => 'md5',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => "1.0"
            ];
            
            $model = new model\CarrierWinit();
            
            $order_info = $model->getOrderInfoByOrderId($order_id);
            
            if ($order_info['errorCode'] == 1) {
                $json['msg'] = $order_info['errorMsg'];
                break;
            }
        
            winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\CreateOutboundInfoDataType';
            
            foreach($order_info['data']['productList'] as $list) {
                $product_types[] = new winit\ProductDataType($list);
            }
            
            unset($order_info['data']['productList']);
        
            $out_data_type = new winit\CreateOutboundInfoDataType($order_info['data']);
        
            $out_data_type->productList = $product_types;
            
            $info_data['data'] = $out_data_type->toJson();
            
            $info_data['sign'] = $this->getSign($info_data, $this->token);
        
            $info_data['data'] = $out_data_type;
        
            $base_type = new winit\BaseRequestType($info_data);
        
            $service = new service\CarrierWinit($this->config);
        
            $result = $service->createOutboundInfo($base_type);
            
            if ($result->code == 0) {
                $json['code'] = 200;
                $json['data'] = $result->data->outboundOrderNum;
            } else {
                $json['msg'] = $result->msg;
            }
        
        } while(false);
        
        return $json;
    }
    
    /**
     * 创建出库单(草稿中)
     */
    public function createOutboundInfo()
    {
        $request = Request::instance();
        
        $order_id = $request->param('order_id');
        
        $json = $this->createOutbound($order_id, 'createOutboundInfo');
        
        return json($json);
    }
    
    /**
     * 创建出库单(确认状态)
     */
    public function createOutboundOrder()
    {
        $request = Request::instance();
        
        $order_id = $request->param('order_id');
        
        $json = $this->createOutbound($order_id, 'createOutboundOrder');
        
        return json($json);
    }
    
    /**
     * 提交/作废海外出库单（当出库单为草稿状态中时,用户可通过API提交/作废出库单
     */
    private function manageOutboundOrder($outbound_order_num, $action)
    {   
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $request = Request::instance();
        
        $outbound_order_num = $request->param('order_code');
        
        if (empty($outbound_order_num)) {
            $json['code'] = 400;
            $json['msg'] = '出库单号不能为空!!!';
            
            return json($json);
        }
        
        $info_data = [
            'action' => $action,
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\VoidOutboundOrderDataType';
        
        $void_outbound_type = new winit\VoidOutboundOrderDataType(['outboundOrderNum' => $outbound_order_num]);
        
        $info_data['data'] = $void_outbound_type->toJson();
        
        $info_data['sign'] = $this->getSign($info_data, $this->token);
        
        $info_data['data'] = $void_outbound_type;
        
        $void_outbound_type = new winit\BaseRequestType($info_data);
               
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getHttpRequest($void_outbound_type);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
        }
        
        return $json;
    }
    
    /**
     * 当出库单处于草稿状态时,可用此接口作废
     */
    public function voidOutboundOrder($order_code = '')
    {
        $request = Request::instance();
        
        $order_code = $order_code ?: ($order_code = $request->param('order_code'));
        
        $json = $this->manageOutboundOrder($order_code, 'voidOutboundOrder');
        
        return json($json);
    }
    
    /**
     * 当出库单处于草稿状态时,可用此接口确认为出库中
     */
    public function confirmOutboundOrder($order_code = '')
    {
        $request = Request::instance();
        
        $order_code = $order_code ?: ($order_code = $request->param('order_code'));
        
        $json = $this->manageOutboundOrder($order_code, 'confirmOutboundOrder');
        
        return json($json);
    }
    
    
    /**
     * 当出库单处于草稿状态时，可用此接口修改出库单
     */
    public function updateOutboundOrder($order_id = 0)
    {
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $request = Request::instance();
        
        $order_id = $order_id ?: $request->param('order_id');
        
        $model = new model\CarrierWinit();
            
        $order_info = $model->getOrderByOrderId($order_id);
        
        if (empty($order_info)) {
            $json['code'] = 400;
            $json['msg'] = '订单信息不能为空!!!';
            
            return json($json);
        }
        
        $info_data = [
            'action' => 'updateOutboundOrder',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\UpdateOutboundOrderDataType';
        
        $updateOutboundType = new winit\UpdateOutboundOrderDataType($order_info);
        
        $info_data['data'] = $updateOutboundType->toJson();
        
        $info_data['sign'] = $this->getSign($info_data, $this->token);
        
        $info_data['data'] = $updateOutboundType;
        
        $baseRequestType = new winit\BaseRequestType($info_data);
               
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getHttpRequest($baseRequestType);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
        }
        
        return json_encode($json, \JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 查询出库单详情
     */
    public function queryOutboundOrder($params = null)
    {
       $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $request = Request::instance();
        $params = $params ?: $request->param();
        
        $info_data = [
            'action' => 'queryOutboundOrder',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\QueryOutboundOrderDataType';
        
        $queryOutboundType = new winit\QueryOutboundOrderDataType();
        
        $exist = false;
        
        if (isset($params['order_code']) && intval($params['order_code'])) {
            $queryOutboundType->outboundOrderNum = intval($params['order_code']);
            $exist = true;
        }
        
        if (isset($params['start_date']) && $params['start_date']) {
            $queryOutboundType->startDate = $params['start_date'];
        } elseif (!$exist) {
            $queryOutboundType->startDate = date('Y-m-d', strtotime("-1 day"));
        }
        
        if (isset($params['end_date']) && $params['end_date']) {
            $queryOutboundType->endDate = $params['end_date'];
        } elseif (!$exist) {
            $queryOutboundType->endDate = date('Y-m-d', strtotime("-1 day"));
        }
        
        if (isset($params['page_size']) && intval($params['page_size'])) {
            $queryOutboundType->pageSize = intval($params['page_size']);
        } else {
            $queryOutboundType->pageSize = 200;
        }
        
        if (isset($params['page_num']) && intval($params['page_num'])) {
            $queryOutboundType->pageNum = intval($params['page_num']);
        } else {
            $queryOutboundType->pageNum = 1;
        }
        
        $info_data['data'] = $queryOutboundType->toJson();
        
        $info_data['sign'] = $this->getSign($info_data, $this->token);
        
        $info_data['data'] = $queryOutboundType;
        
        $baseRequestType = new winit\BaseRequestType($info_data);
               
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getHttpRequest($baseRequestType);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
        }
        
        return json_encode($json, \JSON_UNESCAPED_UNICODE); 
    }
    
    /**
     * 查询出库单列表
     */
    public function queryOutboundOrderList($params = null)
    {
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];
        
        $request = Request::instance();
        
        $params = $params ?: $request->param();
        
        $warehouseId = 1005189;
        
        if (empty($warehouseId)) {
            $json['code'] = 400;
            $json['msg'] = '出库单号不能为空!!!';
            
            return json($json);
        }
        
        $info_data = [
            'action' => 'queryOutboundOrderList',
            'app_key' => $this->app_key,
            'data' => '',            
            'format' => 'json',
            'platform' => 'SELLERERP',
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => "1.0"
        ];
        
        winit\BaseRequestType::$propertyTypes['data']['type'] = 'app\carrier\type\winit\QueryOutboundOrderListDataType';
        
        $dataType = new winit\QueryOutboundOrderListDataType();
        
        $dataType->warehouseId = $warehouseId;
        
        if (isset($params['outbound_order_num']) && $params['outbound_order_num']) {
            $dataType->outboundOrderNum = $params['outbound_order_num'];
        }
        
        if (isset($params['seller_order_no']) && $params['seller_order_no']) {
            $dataType->sellerOrderNo = $params['seller_order_no'];
        }
        
        if (isset($params['tracking_no']) && $params['tracking_no']) {
            $dataType->trackingNo = $params['tracking_no'];
        }
        
        if (isset($params['tracking_no']) && $params['tracking_no']) {
            $dataType->trackingNo = $params['tracking_no'];
        }
        
        if (isset($params['receiver_name']) && $params['receiver_name']) {
            $dataType->receiverName = $params['receiver_name'];
        }
        
        if (isset($params['booking_operator']) && $params['booking_operator']) {
            $dataType->bookingOperator = $params['booking_operator'];
        }
        
        if (isset($params['product_name']) && $params['product_name']) {
            $dataType->productName = $params['product_name'];
        }
        
        if (isset($params['product_value']) && $params['product_value']) {
            $dataType->productValue = $params['product_value'];
        }
        
        if (isset($params['product_sku']) && $params['product_sku']) {
            $dataType->productSku = $params['product_sku'];
        }
        
        if (isset($params['share_order_type']) && $params['share_order_type']) {
            $dataType->shareOrderType = $params['share_order_type'];
        } else {
            // $dataType->shareOrderType = 1;
        }
        
        if (isset($params['date_ordered_start_date']) && $params['date_ordered_start_date']) {
            $dataType->dateOrderedStartDate = $params['date_ordered_start_date'];
        } else {
            $dataType->dateOrderedStartDate = date('Y-m-d', strtotime("-10 day"));
        }
        
        if (isset($params['date_ordered_end_date']) && $params['date_ordered_end_date']) {
            $dataType->dateOrderedEndDate = $params['date_ordered_end_date'];
        } else {
            $dataType->dateOrderedEndDate = date('Y-m-d');
        }
        
        if (isset($params['status']) && $params['status']) {
            $dataType->status = $params['status'];
        } else {
            $dataType->status = 'valid';
        }
        
        if (isset($params['page_size']) && intval($params['page_size'])) {
            $dataType->pageSize = intval($params['page_size']);
        } else {
            $dataType->pageSize = 100;
        }
        
        if (isset($params['page_num']) && intval($params['page_num'])) {
            $dataType->pageNum = intval($params['page_num']);
        } else {
            $dataType->pageNum = 1;
        }
        
        $info_data['data'] = $dataType->toJson();
        
        $info_data['sign'] = $this->getSign($info_data, $this->token);
        
        $info_data['data'] = $dataType;
        
        $baseRequestType = new winit\BaseRequestType($info_data);
               
        $service = new service\CarrierWinit($this->config);
        
        $result = $service->getHttpRequest($baseRequestType);
        
        if ($result->code == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg']  = $result->msg;
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
}
