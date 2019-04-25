<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\vova\VovaOrder as  VovaOrderModel;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class VovaOrder extends Cache
{
    private $key = 'table:vova:order:';
    private $set = 'table:vova:order:set';

    private $orderSyncTime = 'order_sync_time';

    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number vova订单id
     * @param array $data
     * @return array|mixed
     */
    public function orderUpdateTime($accountId, $order_number, $data = [])
    {
        $key = $this->getOrderKey($accountId);

        if ($data) {
            $this->redis->zAdd($this->set, $accountId);
            $this->redis->hset($key, $order_number, json_encode($data));
            return true;
        }
        $result = json_decode($this->redis->hget($key, $order_number), true);
        return $result ? $result : [];
    }

    private function execute()
    {
        $key = 'hash:VovaOrderUpdateTime';
        $list = VovaOrderModel::field('id,order_sn,confirm_time')->where(['confirm_time' => ['gt', time() - 3*24*3600]])->select();
        foreach($list as $order) {
            $this->redis->hset($key, $order['order_sn'], json_encode(['id' => $order['id'], 'confirm_time' => $order['confirm_time']]));
        }
    }
    /**
     * 拿取walmart账号最后下载订单更新的时间
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
     * 设置vova账号最后下载订单更新的时间
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
     * 清除过期的订单
     * @param int $time 删除距离现在一定时间订单
     * @return boolean
     */
    public function handleExpire($time = 3*24*3600)
    {
        $key = 'hash:VovaOrderUpdateTime';
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
        $this->redis->hSet('hash:VovaOrderRefundLogs', $key, json_encode($data));
    }

    /**
     * 获取订单-退款操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOrderRefundLogs($key)
    {
        if ($this->redis->hExists('hash:VovarderRefundLogs', $key)) {
            return true;
        }
        return false;
    }

    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }
}
