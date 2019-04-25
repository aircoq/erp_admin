<?php
namespace app\carrier\task;

use app\index\service\AbsTasker;
use service\alinew\AliexpressApi;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressShippingMethod;

class AliShippingMethod extends AbsTasker
{
    public function getCreator() {
        return '翟彬';
    }
    
    public function getDesc() {
        return '拉取Aliexpress支持的物流服务';
    }
    
    public function getName() {
        return '拉取Aliexpress支持的物流服务';
    }
    
    public function getParamRule() {
        return [];
    }
    
    public function execute() {
        $account_list = Cache::store('AliexpressAccount')->getTableRecord();
        if ($account_list) {
            $time = time();            
            foreach ($account_list as $k=>$v) {
                if ($v['id'] != 34) {
                    continue;
                }
                if ($v['is_invalid'] && $v['is_authorization']) {
                    $config['id'] = $v['id'];
                    $config['client_id'] = $v['client_id'];
                    $config['client_secret'] = $v['client_secret'];
                    $config['token'] = $v['access_token'];
                    $config['refreshtoken'] = $v['refresh_token'];

                    $result = AliexpressApi::instance($config)->loader('ShippingMethod')->getShippingMethod();
                    $result = isset($result->result) ? json_decode($result->result, true) : [];
                    if ($result && $result['result_success']) {
                        $items = $result['result_list']['aeop_logistics_service_result'];
                        foreach($items as $item){
                            $shippingMethodModel = new AliexpressShippingMethod();
                            $data['account_id'] = $v['id'];
                            $data['company'] = $item['logistics_company'];
                            $data['shipping_name'] = $item['display_name'];
                            $data['service_name'] = $item['service_name'];
                            $data['min_process_day'] = $item['min_process_day'];
                            $data['max_process_day'] = $item['max_process_day'];
                            $data['create_time'] = $time;
                            $data['update_time'] = $time;

                            $model = $shippingMethodModel->where(['company'=>$data['company'],'service_name'=>$item['service_name']])->find();
                            if (!empty($model)) {
                                $shippingMethodModel->saveShippingMethod($data, $model['id']);
                            } else {
                                $shippingMethodModel->saveShippingMethod($data);
                            }
                        }
                     }
                     break;
                }
                
            }
        }
    }
}
