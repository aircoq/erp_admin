<?php
namespace app\carrier\model;

use app\common\model\OrderDetail;
use app\common\model\OrderPackage;
use app\common\model\ShippingMethod;
use app\common\model\Warehouse;
use think\Model;

/**
 * Created by NetBeans
 * User: user
 * Date: 2016/11/23
 * Time: 9:13
 */
class Carrier4px extends Model
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
    public function getWarehouseInfoById($warehouse_id)
    {
        $warehouse_model = new \app\common\model\Warehouse();
        
        $info = $warehouse_model->where(['id' => $warehouse_id])->find();
        
        return $info;
    }
    
    /**
     * 获取订单信息
     * @param integer order id
     * @return array order info
     */
    public function getOrderPackage($packageId)
    {  
        $result = [
            'code' => 0,
            'msg' => '',
            'data' => []
        ];
        
        do {
            $package = OrderPackage::get($packageId);
            $result['warehouse_id'] = $package->warehouse_id;
            if (empty($package)) {
                $result['errorCode'] = 1;
                $result['msg'] = '订单不存在';
                break;
            }

            $warehouse = Warehouse::get($package->warehouse_id);
            $shippingMethod = ShippingMethod::get($package->shipping_id);


            if (empty($warehouse) || empty($shippingMethod)) {
                $result['errorCode'] = 1;
                $result['msg'] = "{$packageId} 仓库或者邮寄方式不匹配";
                break;
            }

            $details = OrderDetail::all(['order_id'=>$package->order_id]);

            if (empty($details)) {
                $result['errorCode'] = 1;
                $result['msg'] = "{$packageId} 包裹不存在产品详情";
                break;
            }
            
            $msg = '';
            
            foreach($details as $detail) {
                $sku = $this->getCarrierSku($detail->sku_id, $package->warehouse_id);
                if (empty($sku)) {
                    $msg .= $detail->sku_id . '没有找到对应的sku .';
                }
                $items[] = [
                    'sku' => $sku, // 'WL003',
                    'quantity' => $detail->sku_quantity,
                    'skuLabelCode' => ''
                ];
            }
            
            if ($msg) {
                $result['code'] = 1;
                $result['msg'] = $msg;
                break;
            }
            
            $result['data'] = [
                'referenceCode' => $package->order_id,
                'warehouseCode' => $warehouse->code,
                'carrierCode'   => $shippingMethod->code,
                'insureType'    => 'NI', // 不买保险
                'sellCode'      => '',
                'remoteArea'    => 'Y',
                'description'   => '',
                'insureMoney'   => '0.00',
                'platformCode'  => 'E', // eBay平台
                'fbaLabelCode'  => '',
                'consignee' => [
                    'fullName' => $package->consignee,
                    'countryCode' => $package->country_code,
                    'street' => $package->address,
                    'city' => $package->city,
                    'state' => $package->province,
                    'postalCode' => $package->zipcode,
                    'email' => $package->email,
                    'phone' => $package->tel ? $package->tel: $package->mobile,
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
     * 获取4px的sku
     * @param string $skuId
     * @return string 
     */
    private function getCarrierSku($skuId, $warehouse_id)
    {
        $warehouseGoods = new \app\common\model\WarehouseGoods();
        
        $goods = $warehouseGoods->where(['sku_id' => $skuId, 'warehouse_id' => $warehouse_id])->field('thirdparty_goods_sku')->find();
        
        if (empty($goods)) {
            return '';
        } else {
            return $goods->thirdparty_goods_sku;
        }
        
    }
}