<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/7/6
 * Time: 12:00
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;
use think\Exception;

class LogisticsLog extends Cache
{
    const HASH_KEY = 'hash:carrier:log';
    const DATA_KEY = 'carrier:order';


    public function setLogisticsLog($packageNum,$data, $is_write=0)
    {
        if(!$is_write){
            return;
        }
        if(is_array($data)){
            $data = json_encode($data);
        }
        $callInfo = $this->getCallerInfo();
        $key = self::HASH_KEY.':'.date('Ymd');
        if(is_array($callInfo)){
            $callMethod = $callInfo['function'];
            $callClass = $callInfo['class'];
            $key .= ':'.$callClass.':'.$callMethod;
        }
        $key .= ':'.date('H');
        $this->redis->hSet($key,$packageNum.'-'.date('Ymd H:i:s'),$data);
    }

    public function setProductLog($packageNum,$data){
        $baseKey = 'hash:carrier:log:product:'.date('Ymd:H').":";
        $this->redis->hSet($baseKey,$packageNum."-".date('Ymd H:i:s'),json_encode($data));
    }

    private function getCallerInfo()
    {
        try{
            $backtrace = debug_backtrace();
            foreach ($backtrace as $item)
            {
                if(!isset($item['class'])){
                    continue;
                }
                if(strstr($item['class'],'service\shipping\operation')){
                    $arr_class = explode('\\',$item['class']);
                    return [
                        'function'=>$item['function'],
                        'class'=>end($arr_class)
                    ];
                }
            }
            return false;
        }catch (Exception $exception){
            return false;
        }
    }

    /*
     * @desc 设置物流下单信息
     * @number 包裹号
     * @data 下单成功信息
     */
    public function setLogisticsData($number, $shipping_id, $data)
    {
        /*if(!empty($data)){
            $this->redis->Setex(self::DATA_KEY.':'.$shipping_id.':'.$number, 864000,json_encode($data));
            return true;
        }*/
        return false;
    }

    /*
     * @desc 获取包裹下单信息
     * @number 包裹号
     * @data 下单成功信息
     */
    public function getLogisticsData($number, $shipping_id)
    {
        $data = [];
        /*if ($this->redis->Exists(self::DATA_KEY.':'.$shipping_id.':'.$number)) {
            $data = json_decode($this->redis->Get(self::DATA_KEY.':'.$shipping_id.':'.$number), true);
        }*/
        return $data;
    }

    /*
    * @desc 删除下单数据
    * @number 包裹号
    */
    public function delLogisticsData($number)
    {
        if(!$number){
            return;
        }
        /*$key = self::DATA_KEY.':*:'.$number;
        $keys =$this->redis->keys($key);
        foreach ( $keys as $value) {
            $this->redis->del($value);
        }*/
    }

}