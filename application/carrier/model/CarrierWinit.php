<?php
namespace app\carrier\model;

use think\Model;

/**
 * Created by NetBeans
 * User: user
 * Date: 2016/11/23
 * Time: 9:13
 */
class CarrierWinit extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    /**
     * 获取仓库信息
     * @param code string get warehouse information by warehouse code
     * @return array warehouseInfo
     */
    public function getWarehouseInfoByCode($code)
    {
        $warehouse_model = new \app\common\model\Warehouse();
        
        $info = $warehouse_model->get(['code' => $code]);
        
        return $info ? $info->data : [];
    }
    
    /**
     * 获取仓库信息
     * @param code string get warehouse information by warehouse id
     * @return array warehouseInfo
     */
    public function getWarehouseInfoById($id)
    {
        $warehouseModel = new \app\common\model\Warehouse();
        
        $info = $warehouseModel->get(['id' => $id]);
        
        return $info;
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

