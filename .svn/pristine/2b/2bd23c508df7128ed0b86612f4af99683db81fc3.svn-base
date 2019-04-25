<?php
namespace app\api\service;
use app\api\service\Base;
use app\common\cache\Cache;
use app\order\service\ShippingTrackingService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/3/7
 * Time: 16:58
 */
class ShippingTracking extends Base
{
    /**
     * 跟踪号物流信息更新
     * @return array
     * @throws Exception
     */
    public function tracking()
    {
        $tracking = $this->requestData['tracking'];
        $type = $this->requestData['type'];   //  1-上网开始时间 2-妥投
        $time = $this->requestData['time'];
        Cache::handler()->hSet('hash:tracking:log:' . date('Y-m-d', time()), date('Ymd H:i:s'),
            json_encode(['tracking' => $tracking, 'type' => $type, 'time' => $time,'time1' => strtotime($time)]));
        try{
            $time = strtotime($time);
            $service = new ShippingTrackingService();
            $service->update($tracking,$type,$time);
            $this->retData['message'] = '更新成功';
        }catch (Exception $e){
            $this->retData['message'] = '更新失败，失败原因:'.$e->getMessage();
        }
        return $this->retData;
    }
}