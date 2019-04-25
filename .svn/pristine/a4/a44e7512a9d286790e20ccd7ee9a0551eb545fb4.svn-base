<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonOrder as AmazonOrderModel;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class AmazonOrder extends Cache
{
    private $key = 'table:amazon:order:';
    private $set = 'table:amazon:order:set';
    
    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number amazon订单id
     * @param array $data
     * @return array|mixed
     */
    public function orderUpdateTime($accountId, $order_number, $data = [])
    {
        return [];
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
        $key = 'hash:AmazonOrderUpdateTime';
        $list = AmazonOrderModel::field('id,order_number,last_update_time')->where(['last_update_time' => ['gt', time() - 3*24*3600]])->select();
        foreach($list as $order) {
           $this->redis->hset($key, $order['order_number'], json_encode(['id' => $order['id'], 'last_update_time' => $order['last_update_time']]));
        }        
    }
    
    /**
     * 清除过期的订单
     * @param int $time 删除距离现在一定时间订单
     * @return boolean
     */
    public function handleExpire($time = 3*24*3600)
    {
        $key = 'hash:AmazonOrderUpdateTime';
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
        $this->redis->hSet('hash:AmazonOrderRefundLogs', $key, json_encode($data));
    }
    
    /**
     * 获取订单-退款操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOrderRefundLogs($key)
    {
        if ($this->redis->hExists('hash:AmazonOrderRefundLogs', $key)) {
            return true;
        }
        return false;
    }
    
    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }
}
