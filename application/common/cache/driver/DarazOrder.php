<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 18:42
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class DarazOrder extends Cache
{
    const cachePrefix = 'table';
    private $existOrderPrefix = 'cache:daraz_exist_order:orderid_';
    private $syncOrderTimePrefix = "task:daraz:daraz_sync_time";

    /**
     * 判断订单是否存在
     * @param $order_id
     * @return bool
     */
    public function hasOrder($order_id)
    {
        $key = $this->existOrderPrefix.$order_id;
        $res = $this->redis->setnx($key,1);
        $res && $this->redis->expire($key,3*60);   //缓存三分钟
        return !$res;    //设置成功，则订单不存在
    }

    /**
     * 设置订单同步时间
     * @param $account_id
     * @param array $time
     */
    public function setSyncOrderTime($account_id,$time=[])
    {
        if(!empty($time))
        {
            $this->persistRedis->hSet($this->syncOrderTimePrefix,$account_id,json_encode($time));
        }
    }

    /**
     * 抓取订单同步时间
     * @param $account_id
     * @return array|mixed
     */
    public function getSyncOrderTime($account_id)
    {
        if($this->persistRedis->hExists($this->syncOrderTimePrefix,$account_id))
        {
            $result = json_decode($this->persistRedis->hGet($this->syncOrderTimePrefix,$account_id),true);
            return $result ?? [];
        }
        return [];
    }


}