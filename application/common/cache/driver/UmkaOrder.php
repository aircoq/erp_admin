<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\umka\UmkaOrder as UmkaOrderModel;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/5/20
 * Time: 17:44
 */
class UmkaOrder extends Cache
{
    private $key = 'table:umka:order:';
    private $set = 'table:umka:order:set';
    private $orderSyncTime = 'order_sync_time';
    
    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number umka订单id
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
        $key = 'hash:UmkaOrderUpdateTime';
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
        $this->redis->hSet('hash:UmkaOrderRefundLogs', $key, json_encode($data));
    }
    
    /**
     * 获取订单-退款操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOrderRefundLogs($key)
    {
        if ($this->redis->hExists('hash:UmkaOrderRefundLogs', $key)) {
            return true;
        }
        return false;
    }
    
    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }

    /**
     * 拿取pandao账号最后下载订单更新的时间
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
     * 设置pandao账号最后下载订单更新的时间
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
     * 添加pandao订单转本地订单日志
     * @param unknown $key
     * @param array $data
     */
    public function addOrderToLocalLogs($key, $data = [])
    {
        $this->redis->zAdd('hash:UmkaOrderToLocalLogs', $key, json_encode($data));
    }


    /**
     * 获取pandao订单转本地订单日志
     * @param number $start
     * @param number $end
     */
    public function getOrderToLocalLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:UmkaOrderToLocalLogs')) {
            $result = $this->persistRedis->zRange('hash:UmkaOrderToLocalLogs', $start, $end);
        }
        return $result;
    }


    /**
     * 添加pandao上传跟踪单日志
     * @param unknown $key
     * @param array $data
     */
    public function addSynchronousLogs($key, $data = [])
    {
        $this->redis->zAdd('hash:PandaoSynchronousLogs', $key, json_encode($data));
    }

    /**
     * 获取pandao上传跟踪单日志
     * @param number $start
     * @param number $end
     */
    public function getSynchronousLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:UmkaSynchronousLogs')) {
            $result = $this->persistRedis->zRange('hash:UmkaSynchronousLogs', $start, $end);
        }
        return $result;
    }


}
