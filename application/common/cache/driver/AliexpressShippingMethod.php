<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/9/11
 * Time: 14:23
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class AliexpressShippingMethod extends Cache
{
    const HASH_KEY = 'hash:AliLogisticsService';

    /**
     * 根据物流服务代码获取物流服务名称
     * @param string $serviceKey
     * @return mixed|string
     */
    public function getNameByServiceKey(string $serviceKey)
    {
        if($this->redis->hexists(self::HASH_KEY,$serviceKey)){
            $result = json_decode($this->redis->hGet(self::HASH_KEY,$serviceKey), true);
            return !empty($result) ? $result['shipping_name'] : '';
        }
        $logisticsService = \app\common\model\aliexpress\AliexpressShippingMethod::where(['service_name'=>$serviceKey])->field('id,company,shipping_name,service_name')->find();
        if(!empty($logisticsService)){
            $this->redis->hSet(self::HASH_KEY,$serviceKey,json_encode($logisticsService));
        }
        return !empty($logisticsService)?$logisticsService['shipping_name']:'';
    }
}