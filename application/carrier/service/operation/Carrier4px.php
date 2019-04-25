<?php
namespace app\carrier\service\operation;

use think\Request;
use app\common\model\WarehouseGoods as WarehouseGoodsModel;
use app\common\model\OrderDetail as OrderDetailModel;
use app\carrier\service\ShippingMethodBase;
use app\common\model\Warehouse as WarehouseModel;

/**
 * Class carrier4px
 * @package app\carrier\service
 */
class Carrier4px extends ShippingMethodBase
{   
    private $config = [
        'sandbox' => true, 
        'format' => 'json',
        'code' => '4px',
        'token' => '',
        'customerId' => '',
        'sandboxUrl' => 'http://apisandbox.4px.com',
        'productionUrl' => 'http://openapi.4px.com',
        'language' => 'zh_CN'
    ];
    
    /**
     * 获取API信息
     * 
     * @access private
     * @param int $warehouse_id
     * @return int
     */
    private function getApiInfo($warehouse_id) {
        $warehouseInfo = $this->getWarehouseInfoById($warehouse_id);
        
        if (empty($warehouseInfo)) {
            return false;
        } else {
            if ($warehouseInfo['token'] && $warehouseInfo['customer_id']) {
                $this->config['token'] = $warehouseInfo['token'];
                $this->config['customerId'] = $warehouseInfo['customer_id'];
                $this->config['sandbox'] = false;
            } else {               
                $this->config['token'] = 'oDuCfVi88b40oOuMYQUOcTh2b/T+uJdDBsJ+VOrlG6Q=1';
                $this->config['customerId'] = '100800';
            }
        }
        
        return 0;
    }
    
    /**
     * 更新或加载4px产品分类
     */
    public function updateCategory($category = '0') {
        $request = Request::instance();
        
        $shipping_param = new model\ShippingParam();
        
        $category_code = $category;
        
        $category_type = new epx\GetItemCategoryRequestType(['categoryParentCode' => $category_code]);
        
        $service = new service\Carrier4pxService($this->config); 
        
        $result = $service->getItemCategory($category_type);
        
        $lists = [];        
        
        if ($result->errorCode==='0' && count($result->data)) {
            foreach($result->data as $list) {
                $lists[] = [
                    'code' => $list->categoryCode,
                    'name' => $list->categoryName,
                    'type' => 'category',
                    'channel' => '4px'
                ];
            }
            
            $shipping_param->saveAll($lists);
        }
    }
    
    /**
     * 获取产品信息
     */
    public function getItemList($skus)
    {   
        $json = [
            'code' => 200,
            'msg'  => ''
        ];
        
        $request = Request::instance();
        
        $item_list_type = new epx\GetItemListRequestType();
        
        $item_list_type->lstSku = $skus;
               
        $service = new service\Carrier4pxService($this->config);
        
        $result = $service->getItemList($item_list_type);
        
        if ($result->errorCode == 0) {
            $json['data'] = $result->data;
        } else {
            $json['code'] = 400;
            $json['msg'] = $result->errorMsg;
        }
        
        return json_encode($json, \JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 获取仓库信息
     */
    public function getInventory()
    {  
        $json = [
            'code' => 200,
            'msg' => ''
        ];
        
        $request = Request::instance();
        
        $warehouse_id = $request->param('warehouse');
        
        $inventory_type = new epx\GetInventoryRequestType();
        
        $carrier4px_model = new Carrier4pxModel();
        
        // 获取API信息
        $this->getApiInfo($warehouse_id);
        
        $info = $carrier4px_model->getWarehouseInfoById($warehouse_id);
        
        $inventory_type->warehouseCode = $info ? $info->code : '';
        
        $service = new service\Carrier4pxService($this->config);
        
        $result = $service->getInventory($inventory_type);
        
        $wg_model = new \app\common\model\WarehouseGoods();
        
        $save_list = [];
        
        if ($result && $result->errorCode == '0' && count($result->data)) {
            foreach($result->data as $list) {
                $wg_info = $wg_model->get(['warehouse_id' => $info['id'], 'thirdparty_goods_sku' =>$list->sku]);
                if ($wg_info) {
                    \app\common\model\WarehouseGoods::update([
                        'id' => $wg_info->id,
                        'instransit_quantity' => $list->pendingQuantity,
                        'waiting_shipping_quantity' => $list->shippingQuantity,
                        'available_quantity' => $list->availableQuantity,
                        'defects_quantity' => $list->unqualifiedGross,
                        'update_time' => time()
                    ]);
                } else {
                    $goods_sku_model = new \app\common\model\GoodsSku();
                    
                    $sku_info = $goods_sku_model->where(['sku' => $list->sku])->find();
                    if(empty($sku_info)) continue;
                    $save_list[] = [
                        'warehouse_id' => $info['id'],
                        'goods_id' => $sku_info->goods_id,
                        'sku' => $list->sku,
                        'instransit_quantity' => $list->pendingQuantity,
                        'waiting_shipping_quantity' => $list->shippingQuantity,
                        'available_quantity' => $list->availableQuantity,
                        'defects_quantity' => $list->unqualifiedGross,
                        'thirdparty_goods_sku' => $list->sku,
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                }
                                
            }
            
            if ($save_list) {
                $wg_model->saveAll($save_list);
            }
            
            $json['msg'] = '更新成功!!';
        } else {
            $json['msg'] = $result->errorMsg;
            $json['code'] = 400;
        }
        
        return json_encode($json, \JSON_UNESCAPED_UNICODE);       
    }
    
    /**
     * 创建出库单 -- 下单
     * createDevlieryOrder
     * 
     * @param int $id
     * @return array
     */
    public function createOrder($token, $orders)
    {      
        $order       = $orders[0];
        $warehouseId = 0;        
        $info        = $this->formatPackageInfo($order, $warehouseId);
        $json        = [
            'code' => 400, 
            'msg' => '', 
            'data' => ''
        ];
        $this->getApiInfo($warehouseId);                
        do {
            if ($info['errorCode']) { // 组装数据
                $json['msg'] = $info['errorMsg'];                
                break;
            }        
            $result = $this->callOperation($info, 'api/service/woms/order/getOrderCarrier');             
            if ($result['errorCode'] != '0') { // 执行情况
                $json['msg'] = $result['errorMsg'];
                break;
            }            
            if ($result['data']['ack'] == 'N') { // 执行结果
                foreach($result['data']['errors'] as $error) {
                    $json['msg'] .= $error['codeNote'].'.';
                }                
                break;
            }            
            $json['code']                      = 200;           
            $json['process_code']              = $result['data']['documentCode'];
            $json['packageConfirmStatus']      = 1; 
            $json['trackNumber']               = '';
        } while(false);
        
        return $json;
    }
    
    /**
     * 获取邮寄方式
     */
    public function getOrderCarrier()
    {          
        $json = [
            'code' => 200,
            'msg'  => ''
        ];
        
        $request = Request::instance();
        
        // $warehouseCode = $request->param('warehouse');
        
        // $this->getApiInfo(3);
        
        $warehouse_4px = [
            ['code' => '4PHK', 'name' => '递四方香港仓'],
            ['code' => 'AUSY', 'name' => '递四方东莞保税仓'],
            ['code' => 'BJ01', 'name' => '递四方北京一号仓'],
            ['code' => 'CAWH', 'name' => '递四方加拿大库'],
            ['code' => 'DEFR', 'name' => '递四方德国二仓'],
            ['code' => 'DEWC', 'name' => '第四方德国三仓'],
            ['code' => 'DEWH', 'nmae' => '递四方德国库'],
            ['code' => 'DGBS', 'name' => '递四方东莞保税仓'],
            ['code' => 'DGST', 'name' => '递四方东莞B61保税仓'],
            ['code' => 'HK02', 'name' => '递四方香港二仓'],
            ['code' => 'HK03', 'name' => '递四方香港转运仓'],
            ['code' => 'KRIC', 'name' => '递四方韩国仁川仓'],
            ['code' => 'SGP1', 'name' => '递四方新加坡仓'],
            ['code' => 'SH01', 'name' => '递四方上海一号仓'],
            ['code' => 'SHHQ', 'name' => '递四方上海虹桥仓库'],
            ['code' => 'STKJ', 'name' => '递四方沙田快件仓'],
            ['code' => 'SZZW', 'name' => '递四方深圳钟屋仓'],
            ['code' => 'UKLH', 'name' => '递四方英国库'],
            ['code' => 'USLA', 'name' => '递四方美国洛杉矶仓'],
            ['code' => 'USNY', 'name' => '递四方美东纽约仓']
        ];
        
        foreach($warehouse_4px as $ware) {
            $warehouseCode = $ware['code'];
            $result = $this->callOperation(['warehouseCode' => $warehouseCode], 'api/service/woms/order/getOrderCarrier');
            var_dump($result);exit;
        }
            // $carrier_type = new epx\GetOrderCarrierRequestType(['warehouseCode' => $warehouseCode]);
        
            // $service = new service\Carrier4pxService($this->config);
        
            // $result = $service->getOrderCarrier($carrier_type);
            
            // $records = [];
        
            // $sys_ship_method = new \app\common\model\SystemShippingMethod();
        
            /*if ($result->errorCode == '0'&& count($result->data)) {           
            foreach($result->data as $list) {
                if (!$sys_ship_method->check(['code' => $list->carrierCode, 'warehouse_code' => $warehouseCode, 'carrier' => '4px'])) {
                    $records[] = [
                      'shortname' => $list->carrierName,
                      'fullname'  => $list->carrierEName,
                      'code'      => $list->carrierCode,
                      'warehouse_code'=> $warehouseCode,
                      'carrier'=> '4px',
                      'type' => 1
                    ];
                }
            }
            $sys_ship_method->saveAll($records);
           
            $json['msg'] = '加载成功!!';
        } else {
                $json['code'] = 400;
                $json['msg'] = $result->errorMsg;
            }
        } */
        
        return json($json, 200);
    }
    
    /**
     * 获取出库单信息
     */
    public function getDeliveryOrder($order_code = '', $warehouse_id = 0)
    {
        $json = [
            'code' => 400,
            'msg' => '',
            'data' => []
        ];        
        $this->getApiInfo($warehouse_id);       
        if (empty($order_code)) {
            $json['msg'] = '出库单号不能为空';            
            return json($json);
        }               
        $result = $this->callOperation(['orderCode' => $order_code], 'api/service/woms/order/getDeliveryOrder');        
        if ($result['errorCode'] == 0 && $result['data']) {
            $json['code'] = 200;
            $json['data'] = $result['data'];
        } else if($result['errorCode'] == 0 && !$result['data']) {
            $json['msg'] = $order_code.' 出库单号不存在，未查询到信息!!';
        } else{
            $json['msg']  = $result['errorMsg'];
        }
        
        return json($json, 200);
    }
    
    /**
     * 获取出库单费用信息
     */
    public function getOrderFee($order_code = '', $warehouse_id = 0)
    {
         $json = [
            'code' => 400,
            'msg' => '',
            'data' => []
        ];    
        $this->getApiInfo($warehouse_id);        
        if (empty($order_code)) {
            $json['msg'] = '出库单号不能为空';            
            return json($json);
        } 
        $result = $this->callOperation(['orderCode' => $order_code], 'api/service/woms/order/getOrderFee');  
        if ($result['errorCode'] == 0 && $result['data']) {
            $json['code'] = 200;
            $json['data'] = $result['data'];
        } else if($result['errorCode'] == 0 && !$result['data']) {
            $json['msg'] = $order_code.' 出库单号不存在，未查询到信息!!';
        } else{
            $json['msg']  = $result['errorMsg'];
        }
        
        return json($json, 200);
    }
    
    /**
     * 取消出库单号
     * 返回Y为取消操作成功，并不代表订单取消成功
     */
    public function cancelDeliveryOrder($order_code = '', $warehouse_id = 0)
    {   
        $json = [
            'code' => 200,
            'msg'  => '',
            'data' => ''
        ];        
        if (empty($order_code)) {
            $json['code'] = 1;
            $json['msg'] = '订单号不为空';            
            return json($json, 400);
        }       
        $this->getAPIInfo($warehouse_id);
        $result = $this->callOperation(['orderCode' => $order_code], 'api/service/woms/order/cancelDeliveryOrder');
        if ($result['errorCode'] == 0) {
            if ($result['data']['ack'] == 'Y') {
                $json['msg'] = $result['data']['documentCode'] . '出库单取消成功';
            } else {
                $json['code'] = 400;                
                foreach ($result['data']['errors'] as $error) {
                    $json['msg'] .= $error['codeNote'] . '.';
                }
            }
        } else {
            $json['code'] = 400;
            $json['msg']  = $result['errorMsg'];
        }        
        return json($json, true);
    }
    
    /**
     * 获取所有出库单
     */
    public function getDeliveryOrderList($params = null) 
    {   
        $json = [
            'code' => 200, 
            'msg'  => '', 
            'data' => []
        ];
        $warehouse_id = isset($params['warehouse']) ? $params['warehouse'] : 0;
        $warehouseCode = '';
        $search_data = [];
        $this->getApiInfo($warehouse_id);
               
        if (isset($params['order_code']) && $params['order_code']) {
            $search_data['orderCode'] = $params['order_code'];
        }
        
        if (isset($params['carrier_code']) && $params['carrier_code']) {
            $search_data['carrierCode'] = $params['carrier_code'];
        }
        
        if (isset($params['area_code']) && $params['area_code']) {
            $search_data['areaCode'] = $params['area_code'];
        }
        
        if (isset($params['reference_code']) && $params['reference_code']) {
            $search_data['referenceCode'] = $params['reference_code'];
        }
        
        if (isset($params['create_date_begin']) && $params['create_date_begin']) {
            $search_data['createDateBegin'] = $params['create_date_begin'];
        }
        
        if (isset($params['create_date_end']) && $params['create_date_end']) {
            $search_data['createDateEnd'] = $params['create_date_end'];
        }
        
        if (isset($params['order_status_code']) && $params['order_status_code']) {
            $$search_data['orderStatusCode'] = $params['order_status_code'];
        } else {
            $$search_data['orderStatusCode'] = 'S';
        }
        
        if (isset($params['shipment_date_begin']) && $params['shipment_date_begin']) {
            $$search_data['shipmentDateBegin'] = $params['shipment_date_begin'];
        } else {
            $$search_data['shipmentDateBegin'] = date('Y-m-d H:i:s', strtotime("-1 day"));
        }
        
        if (isset($params['shipment_date_end']) && $params['shipment_date_end']) {
            $$search_data['shipmentDateEnd'] = $params['shipment_date_end'];
        } else {
            $search_data['shipmentDateEnd'] = date('Y-m-d H:i:s');
        }
        
        $result = $this->callOperation(['orderCode' => $order_code], 'api/service/woms/order/getDeliveryOrderList');
        if ($result['errorCode'] == 0) {
            $json['data'] = $result['data'];
        } else {
            $json['code'] = 400;
            $json['msg'] = $result['errorMsg'];
        }
        
        return json($json, 200);
    }
    
    private function index()
    {
        $wg_model = new \app\common\model\WarehouseGoods();
        
        $lists = $wg_model->where(['warehouse_id' => 3])->select();
        
        $skus = [];
        
        foreach ($lists as $list) {
            $skus[] = $list->thirdparty_goods_sku;
        }
        
        $goods_info = $this->getItemList($skus);
        
        $object = json_decode($goods_info);
        
        foreach ($object->data as $goods) {
            $goods_model = new \app\common\model\Goods();
        
            $goods_sku_model = new \app\common\model\GoodsSku();
            
            $goods_model->save([
                'spu' => $goods->sku,
                'title' => $goods->itemName,
                'en_title' => $goods->itemName,
                'description' => $goods->description?: $goods->itemName,
                'create_time' => time(),
                'update_time' => time(),
                'publish_time' => time(),
                'weight' => $goods->weight,
                'width' => $goods->width,
                'depth' => $goods->length,
                'height' => $goods->height
            ]);
            
            $goods_sku_model->save([
                'goods_id' => $goods_model->id,
                'sku' => $goods->sku,
                'name' => $goods->itemName,
                'title' => $goods->itemName,
                'create_time' => time(),
                'update_time' => time()
            ]);
            
        }
    }
    
    /**
     * 获取订单信息
     * @param integer order id
     * @return array order info
     */
    private function getOrderInfoByOrderId($package_id, &$warehouse_id)
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
            $warehouse_id = $order_info->warehouse_id;
            $warehouse_info = \app\common\model\Warehouse::get(['id' => $order_info->warehouse_id]);
            $sm_info = \app\common\model\ShippingMethod::get(['id' => $order_info->shipping_id]);
            if (empty($warehouse_info) || empty($sm_info)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '仓库或者邮寄方式不匹配';
                break;
            }       
            $detail_info = OrderDetailModel::all(['order_id' => $order_id]);           
            if (empty($detail_info)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '订单不存在产品详情';
                break;
            }
            
            $errorMsg = '';           
            foreach($detail_info as $list) {
                $sku = $this->getCarrierSku($list->channel_sku, $order_info->warehouse_id);
                if (empty($sku)) {
                    $errorMsg .= $list->channel_sku . '没有找到对应的sku .';
                }
                $items[] = [
                    'sku' => $sku, // 'WL003',
                    'quantity' => $list->sku_quantity,
                    'skuLabelCode' => ''
                ];
            }
            
            if ($errorMsg) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = $errorMsg;
                break;
            }
            
            $result['data'] = [
                'referenceCode' => $order_info->order_number,
                'warehouseCode' => $warehouse_info->code,
                'carrierCode'   => $sm_info->code,
                'insureType'    => 'NI', // 不买保险
                'sellCode'      => '',
                'remoteArea'    => 'Y',
                'description'   => '',
                'insureMoney'   => '0.00',
                'platformCode'  => 'E', // eBay平台
                'fbaLabelCode'  => '',
                'consignee' => [
                    'fullName' => $order_info->consignee,
                    'countryCode' => $order_info->country_code,
                    'street' => $order_info->address,
                    'city' => $order_info->city,
                    'state' => $order_info->province,
                    'postalCode' => $order_info->zipcode,
                    'email' => $order_info->email,
                    'phone' => $order_info->tel ? $order_info->tel: $order_info->mobile,
                    'company' => '',
                    'doorplate' => '',
                    'cardId' => ''
                ],
                'items' => $items
            ];
            
        } while(false);
        
        return $result;
    }
    
    /**
     * 获取订单信息
     * @param array $order
     * @param int warehouse_id
     * @return array order info
     */
    private function formatPackageInfo(&$order, &$warehouseId)
    {  
        $result = [
            'errorCode' => 0,
            'errorMsg' => '',
            'data' => []
        ];        
        do {                 
            if (empty($order)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '订单不存在';
                break;
            }
            $warehouseId = $order['warehouse_id'];
            $warehouseInfo = WarehouseModel::get(['id' => $warehouseId]);        
            $smInfo = ShippingMethod::get(['id' => $order['shipping_id']]);            
            if (empty($warehouseInfo) || empty($smInfo)) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '仓库或者邮寄方式不匹配';
                break;
            }  
                     
            if (empty($order['product'])) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = '订单不存在产品详情';
                break;
            }
            
            $errorMsg = '';           
            foreach($order['product'] as $list) {
                $sku = $this->getCarrierSku($list['sku'], $warehouseId);
                if (empty($sku)) {
                    $errorMsg .= $list['sku'] . '没有找到对应的sku .';
                }
                $items[] = [
                    'sku' => $sku, // 'WL003',
                    'quantity' => $list['qty'],
                    'skuLabelCode' => ''
                ];
            }
            
            if ($errorMsg) {
                $result['errorCode'] = 1;
                $result['errorMsg'] = $errorMsg;
                break;
            }
            
            $result['data'] = [
                'referenceCode' => $order['number'],
                'warehouseCode' => $warehouseInfo['code'],
                'carrierCode'   => $smInfo['code'],
                'insureType'    => 'NI', // 不买保险
                'sellCode'      => '',
                'remoteArea'    => 'Y',
                'description'   => '',
                'insureMoney'   => '0.00',
                'platformCode'  => 'E', // eBay平台
                'fbaLabelCode'  => '',
                'consignee'     => [
                    'fullName'    => $order['sender']['sender_name'],
                    'countryCode' => $order['sender']['sender_country'],
                    'street'      => $order['sender']['sender_street'],
                    'city'        => $order['sender']['sender_city'],
                    'state'       => $order['sender']['sender_state'],
                    'postalCode'  => $order['sender']['sender_zipcode'],
                    'email'       => $order['sender']['email'],
                    'phone'       => $order['sender']['sender_phone'] ? $order['sender']['sender_phone'] : $order['sender']['sender_mobile'],
                    'company'     => '',
                    'doorplate'   => '',
                    'cardId'      => ''
                ],
                'items'           => $items
            ];
            
        } while(false);
        
        return $result;
    }
    
      /**
     * 获取4px的sku
     * @param string $sku
     * @return string 
     */
    private function getCarrierSku($sku, $warehouse_id)
    {
        /*if (false !== strpos($sku, '|')) {
            $arr = explode('|', $sku);
            $sku = $arr[0];
        }*/       
        $wareGoodsModel = new WarehouseGoodsModel();        
        $goodsInfo = $wareGoodsModel->where(['sku' => $sku, 'warehouse_id' => $warehouse_id])->field('thirdparty_goods_sku')->find();        
        if (empty($goodsInfo)) {
            return '';
        } else {
            return $goodsInfo->thirdparty_goods_sku;
        }        
    }
    
    /**
     * curl 操作
     * @param string $url request url
     * @param string $post_data request parameters
     * @param array  $headers An associative array of HTTP headers
     */
    private function curl($url,$post_data, $headers) {
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
     * @param string 请求方法名
     * @return object json decode.
     */
    private function callOperation($data, $path)
    {
        $url       = $this->getUrl($path);       
        $post_data = json_encode($data);        
        $headers[] = 'Content-Type:application/json';       
        $headers[] = 'Content-Length:' . strlen($post_data);       
	$response  = $this->curl($url, $post_data, $headers);       
        $result    = json_decode($response, true);        
        if (!$result) {                   
            $result['errorCode'] = 400;                      
            $result['errorMsg']  = '系统错误!!';
        }        
        return $result;
    }

    /**
     * 组织请求url及参数
     *
     * @param string $path
     * @return string $url
     */
    private function getUrl($path)
    {
        $url  = $this->config['sandbox'] ? $this->config['sandboxUrl'] : $this->config['productionUrl'];
        $url .= '/'. $path . '?token='. $this->config['token'];
        $url .= '&customerId='. $this->config['customerId'];
        $url .= '&language='. $this->config['language'];
        $url .= '&format=json';
        
        return $url;
    }
    
    /**
     * 获取仓库信息
     * 
     * @param int $warehouse_id
     * @return array $info
     */
    private function getWarehouseInfoById($warehouse_id)
    {
        $warehouseModel = new WarehouseModel();        
        $info           = $warehouseModel->where(['id' => $warehouse_id])->find();       
        return $info ? $info->toArray() : [];
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
}

