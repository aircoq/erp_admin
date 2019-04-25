<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\joom\JoomOrder as JoomOrderModel;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/5/17
 * Time: 11:44
 */
class JoomOrder extends Cache
{
    private $key = 'table:joom:order:';
    private $set = 'table:joom:order:set';
    private $orderSyncTime = 'order_sync_time';
    
    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number joom订单id
     * @param array $data
     * @return array|mixed
     */
    public function orderUpdateTime($accountId, $order_number, $data = [])
    {
        $key = $this->getOrderKey($accountId);
        if ($data) {
//            $this->redis->hset($key, $order_number, json_encode($data));
            return true;
        }
        $result = json_decode($this->redis->hget($key, $order_number), true);
        return $result ? $result : [];
    }

    
    /**
     * 清除过期的订单
     * @param int $time 删除距离现在一定时间订单
     * @return boolean
     */
    public function handleExpire($time = 3*24*3600)
    {
        $key = 'hash:JoomOrderUpdateTime';
        $last_update_time = time() - $time;
        $orders = $this->redis->hGetAll($key);
        foreach($orders as $order_number => $order) {
            $info = json_decode($order, true);
            $info['last_update_time'] <= $last_update_time ? $this->redis->hDel($key, $order_number) : '';
        }
        
        return true;
    }
    
    
    /**
     * 添加订单-退款操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addOrderRefundLogs($key, $data = [])
    {
        $this->redis->hSet('hash:JoomOrderRefundLogs', $key, json_encode($data));
    }
    
    /**
     * 获取订单-退款操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOrderRefundLogs($key)
    {
        if ($this->redis->hExists('hash:JoomOrderRefundLogs', $key)) {
            return true;
        }
        return false;
    }
    
    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }




    /**
     * 拿取joom账号最后下载订单更新的时间
     * @param int $account_id
     * @return array
     */
    public function getOrderSyncTime($account_id)
    {
        $key = $this->key . $this->orderSyncTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            $result = json_decode($this->persistRedis->hget($key, $account_id), true);
            return $result ?? [];
        }
        return [];
    }

    /**
     * 设置joom账号最后下载订单更新的时间
     *  @param int $account_id
     *  @param array $time
     */
    public function setOrderSyncTime($account_id, $time = [])
    {
        $key = $this->key . $this->orderSyncTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, json_encode($time));
        }
    }

    /**
     * 添加joom订单转本地订单日志
     * @param unknown $key
     * @param array $data
     */
    public function addOrderToLocalLogs($key, $data = [])
    {
//        $this->redis->zAdd('hash:JoomOrderToLocalLogs', $key, json_encode($data));
    }


    /**
     * 获取joom订单转本地订单日志
     * @param number $start
     * @param number $end
     */
    public function getOrderToLocalLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:JoomOrderToLocalLogs')) {
            $result = $this->persistRedis->zRange('hash:JoomOrderToLocalLogs', $start, $end);
        }
        return $result;
    }


    /**
     * 添加joom上传跟踪单日志
     * @param unknown $key
     * @param array $data
     */
    public function addSynchronousLogs($key, $data = [])
    {
        $this->redis->zAdd('hash:JoomSynchronousLogs', $key, json_encode($data));
    }

    /**
     * 获取joom上传跟踪单日志
     * @param number $start
     * @param number $end
     */
    public function getSynchronousLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:JoomSynchronousLogs')) {
            $result = $this->persistRedis->zRange('hash:JoomSynchronousLogs', $start, $end);
        }
        return $result;
    }


}
