<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/6/30
 * Time: 19:51
 */

namespace app\carrier\service;


use app\carrier\exception\CarrierException;
use app\common\cache\Cache;
use erp\AbsServer;
use service\shipping\ShippingApi;
use app\common\model\ShippingMethod as ShippingMethodModel;
use think\Db;
use think\Exception;
use \app\warehouse\service\Carrier as CarrierService;

class ShippingMethodService extends AbsServer
{
    /**
     * 保存物流渠道
     * @param $carrierId
     * @return bool
     * @throws CarrierException
     */
    public function synShippingMethod($carrierId)
    {
        try{
            $result = $this->downShippingMethod($carrierId);
            foreach($result as $item){
                $shipping = ShippingMethodModel::where(['code'=>$item['code'],'carrier_id'=>$carrierId])->find();
                if($shipping){
                    //原本有的不修改
                   /* ShippingMethodModel::update(['shortname'=>$item['name']], ['id'=>$shipping['id']]);

                    //添加日志
                    (new CarrierService())->addLog($shipping, array('shortname'=>$item['name']), 2, $shipping['id']);
                     Cache::store('shipping')->delShipping($shipping['id']);*/
                }else{
                    $new_data = ShippingMethodModel::create(['shortname'=>$item['name'],'code'=>$item['code'],'carrier_id'=>$carrierId]);

                    (new CarrierService())->addLog([], array('shortname'=>$item['name'],'code'=>$item['code'],'carrier_id'=>$carrierId), 2, $new_data->id);
                }
            }
            return true;
        }catch (Exception $ex){
            throw new CarrierException($ex->getMessage());
        }
    }

    /**
     * 调用API获取物流商渠道
     * @param $carrierId //物流服务商ID
     * @return mixed
     * @throws CarrierException
     */
    public function downShippingMethod($carrierId)
    {
        $carrier = Cache::store('carrier')->getCarrier($carrierId);
        $config = [
            'client_id'=>$carrier['interface_user_name'],
            'client_secret'=>$carrier['interface_user_key'],
            'interface_user_password'=>$carrier['interface_user_password'],
            'accessToken'=>$carrier['interface_token'],
            'pickup_account_id'=>$carrier['pickup_account_id'],
            'customer_code'=>$carrier['customer_code'],
            'carrier_code'=>$carrier['code'],
        ];
        $classType = $carrier['index'] ; //物流商的类名
        $server = ShippingApi::instance()->loader($classType);
        $result = $server->getExpress($config);
        if(isset($result['success'])&&$result['success']){
            return $result['data'];
        }else{
            if(isset($result['error']) && $result['error']['error_msg']){
                throw new CarrierException('获取物流方式失败，'.$result['error']['error_msg']);
            } else {
                throw new CarrierException('获取物流方式失败');
            }
        }
    }

    /**
     * 判断是否有api对接
     * @param int $shipping_id
     * @return bool
     */
    public function hasApi($shipping_id)
    {
        $shipping = Cache::store('shipping')->getShipping($shipping_id);
        if(!empty($shipping)){
            $carrier = Cache::store('carrier')->getCarrier($shipping['carrier_id']);
            if(!empty($carrier) && $carrier['type']){
                return  true;
            }
        }
        return false;
    }
}