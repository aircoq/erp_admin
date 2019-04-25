<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8
 * Time: 14:17
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class OberloOrder extends Cache
{
    private $taskPrefix = 'task:oberlo:';
    private $orderSyncTime = 'order_sync_time';

    /**
     * 设置订单同步时间
     * @param $account_id
     * @param array $time
     */
    public function setOrderSyncTime($account_id, $time=[])
    {
       $key = $this->taskPrefix.$this->orderSyncTime;
       if(!empty($time))
       {
           $this->persistRedis->hSet($key, $account_id, json_encode($time));
       }
    }

    /**
     * 获取订单同步时间
     * @param $account_id
     * @return array|mixed
     */
    public function getOrderSyncTime($account_id)
    {
        $key = $this->taskPrefix.$this->orderSyncTime;
        $result = [];
        if($this->persistRedis->hExists($key,$account_id))
        {
            $result = json_decode($this->persistRedis->hGet($key,$account_id),true);
        }
        return $result ?? [];
    }

    /**
     * 插入订单的时候避免相同订单插入
     */
    public function filterOrder($order_id)
    {
       $key = "cache:oberlo_".$order_id;
       $res = $this->redis->setnx($key,1);
       $res && $this->redis->expire($key,3*60);
       return !$res;
    }

    /**
     * 获取所有国家  以country_en_name为键值
     */
    public function getCountry()
    {
        $key = $this->taskPrefix.'allcountry';
        if($this->redis->exists($key))
        {
            return unserialize($this->redis->get($key));
        }else{
            $arr = [];
            $countrys = Cache::store("Country")->getCountry();
            foreach ($countrys as $c)
            {
                $arr[$c['country_en_name']] = $c;
            }
            $this->redis->set($key,serialize($arr));
            return $arr;
        }

    }

}