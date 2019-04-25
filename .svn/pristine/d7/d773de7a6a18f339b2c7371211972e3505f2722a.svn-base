<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ebay\EbayOrder as EbayOrderModel;
use app\common\model\ebay\EbayOrderDetail as EbayOrderDetailModel;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class EbayOrder extends Cache
{
    private $key = 'task:ebay:order:';
    private $set = 'task:ebay:order:set';
    private $shop_cache = "cache:shop_cache:";
    
    /** 获取属性信息
     * @param string $order_number ebay订单id
     * @param array $data
     * @return array|mixed
     */
    public function orderUpdateTime($accountId, $order_number, $data = [])
    {
        $key = $this->getOrderKey($accountId);
        if ($data) {
            $this->redis->zAdd($this->set, $accountId);
            $this->redis->hSet($key, $order_number, json_encode($data));
            return true;
        }
        $result = json_decode($this->redis->hget($key, $order_number), true);
        if($result){
            return $result;
        }
        
        return [];
    }
    
    /**
     * 获取订单order key
     * @param imt $accountId
     * @return string
     */
    private function getOrderKey($accountId)
    {
        return $this->key . $accountId;
    }
    
    /**
     * 清除过期的订单
     * @param int $time 删除距离现在一定时间订单
     * @return boolean
     */
    public function delExpireOrder($time = 3*24*3600)
    {
        $key = 'hash:EbayOrderUpdateTime';
        $last_update_time = time() - $time;
        $orders = $this->redis->hGetAll($key);
        foreach($orders as $order_number => $order) {
            $info = json_decode($order, true);
            $info['last_update_time'] <= $last_update_time ? $this->redis->hDel($key, $order_number) : '';
        }
    
        return true;
    }
    
    

    /**
     * 缓存交易号对应的订单信息
     * @param unknown $transaction_id
     * @param array $data
     * @return boolean|mixed
     */
    public function orderByTransid($transaction_id, $data = [])
    {
        //Cache::handler()->del('hash:EbayOrderTransaction'); //删除
    
        $key = 'hash:EbayOrderTransaction';
        if ($data) {
            $this->redis->hset($key, $transaction_id, json_encode($data));
            return true;
        }
        $result = json_decode($this->redis->hget($key, $transaction_id), true);
        if($result){
            return $result;
        }

        //从数据库中获取值
        $detail = EbayOrderDetailModel::field('order_id')->where(['transaction_id'=>$transaction_id])->find();
        if($detail){
            $order = EbayOrderModel::field('id')->where(['order_id'=>$detail['order_id']])->find();
            $data = [
                'id'  => $order['id'],
                'order_id' => $detail['order_id']
            ];
            $this->redis->hset($key, $transaction_id, json_encode($data));
            return $data;
        }
        
        return [];

    }
    

    /**
     * 添加测试日志
     * @param unknown $key
     * @param array $data
     */
    public function addOrderLogs($key, $data = [])
    {
        $this->redis->zAdd('hash:EbayOrderLogs', $key, json_encode($data));
    }
    
    /**
     * 获取测试日志
     * @param number $start
     * @param number $end
     */
    public function getOrderLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:EbayOrderLogs')) {
            $result = $this->persistRedis->zRange('hash:EbayOrderLogs', $start, $end);
        }
        return $result;
    }
    
    
    /**
     * 添加ebay订单转本地订单日志
     * @param unknown $key
     * @param array $data
     */
    public function addOrderToLocalLogs($key, $data = [])
    {
        $this->redis->zAdd('hash:EbayOrderToLocalLogs', $key, json_encode($data));
    }
    
    
    /**
     * 获取ebay订单转本地订单日志
     * @param number $start
     * @param number $end
     */
    public function getOrderToLocalLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:EbayOrderToLocalLogs')) {
            $result = $this->persistRedis->zRange('hash:EbayOrderToLocalLogs', $start, $end);
        }
        return $result;
    }
    
    
    /**
     * 添加ebay上传跟踪单日志
     * @param unknown $key
     * @param array $data
     */
    public function addSynchronousLogs($key, $data = [])
    {
        $this->redis->zAdd('hash:EbaySynchronousLogs', $key, json_encode($data));
    }

    /**
     * 获取ebay上传跟踪单日志
     * @param number $start
     * @param number $end
     */
    public function getSynchronousLogs($start = 0, $end = 50)
    {
        $result = [];
        if($this->persistRedis->exists('hash:EbaySynchronousLogs')) {
            $result = $this->persistRedis->zRange('hash:EbaySynchronousLogs', $start, $end);
        }
        return $result;
    }

    /**
     * 记录推送到系统订单时没有明细的订单
     * @param $orderId
     *
     */
    public function setPushNoDetailLogs($orderId)
    {
       $time = time();
       $data = $this->getPushNoDetailLogs($orderId);
       if(!$data)
       {
           $data['retry_number'] = 1;
           $data['last_retry_time'] = $time;
       }
        //距离上次尝试的时间间隔超过3分钟的才会计数
        if($time - $data['last_retry_time'] > 3*60)
        {
            $data['retry_number'] += 1;
            $data['last_retry_time'] = time();
        }
        $this->redis->hSet($this->getNODetailKey(),$orderId,json_encode($data));
        return $data['retry_number'];
    }

    /**
     * 获取记录的没有明细的订单
     * @param $orderId
     * @return mixed
     */
    public function getPushNoDetailLogs($orderId)
    {
        return json_decode($this->redis->hGet($this->getNODetailKey(),$orderId),true);
    }

    /**
     * 删除记录的订单
     * @param $orderId
     */
    public function delNoDetailOrder($orderId)
    {
        if($this->redis->hExists($this->getNODetailKey(),$orderId))
        {
            $this->redis->hDel($this->getNODetailKey(),$orderId);
        }
    }

    private function getNODetailKey()
    {
        return $this->key."push_ebay_no_detail";
    }

    /**
     * 判断订单是否存在缓存
     * @param $order_id
     * @return bool
     */
    public function hasOrder($order_id)
    {
        $key = "cache:pull_ebay_order:".$order_id;
        $res = $this->redis->setnx($key,1);
        $res && $this->redis->expire($key,3*60);
        return !$res;
    }

    /**
     * 资金核算缓存
     * @param $name
     * @param $sql
     * @param $data
     */
    public function setShopCache($name,$sql,$data,$ttl=24*3600)
    {
        $key = $this->shop_cache.$name.":".md5($sql);
        if($data)
        {
            $this->redis->setex($key,$ttl,json_encode($data));
        }
    }

    public function getShopCache($name,$sql)
    {
        $key = $this->shop_cache.$name.":".md5($sql);

        $data = json_decode($this->redis->get($key),true);

        if(empty($data))
        {
            return false;
        }

        return $data;
    }

    public function delShopCache($name,$sql)
    {
        $key = $this->shop_cache.$name.":".md5($sql);

        $this->redis->del($key);
    }

}
