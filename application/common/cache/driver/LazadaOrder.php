<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\lazada\LazadaOrder as LazadaOrderModel;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class LazadaOrder extends Cache
{
    private $key = 'table:lazada:order:';
    private $set = 'table:lazada:order:set';

    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number amazon订单id
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
        $key = 'hash:LazadaOrderUpdateTime';
        $list = LazadaOrderModel::field('id,order_id,update_at')->where(['update_at' => ['gt', time() - 3*24*3600]])->select();
        foreach($list as $order) {
            $this->redis->hset($key, $order['order_id'], json_encode(['id' => $order['id'], 'update_at' => $order['update_at']]));
        }
    }

    /**
     * 清除过期的订单
     * @param int $time 删除距离现在一定时间订单
     * @return boolean
     */
    public function handleExpire($time = 3*24*3600)
    {
        $key = 'hash:LazadaOrderUpdateTime';
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
        $this->redis->hSet('hash:LazadaOrderRefundLogs', $key, json_encode($data));
    }

    /**
     * 获取订单-退款操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOrderRefundLogs($key)
    {
        if ($this->redis->hExists('hash:LazadaOrderRefundLogs', $key)) {
            return true;
        }
        return false;
    }

    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }
}
