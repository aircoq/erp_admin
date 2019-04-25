<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressOnlineOrder as AliModel;
use think\Db;

class AliexpressOnlineOrder extends Cache 
{
    private $setKey = 'task:aliexpress:order:set';

    private $key = 'task:aliexpress:order';
    /*
     *设置订单最后修改时间
     */
    public function getModifiedTime($accountId, $orderId)
    {
        /*$key = $this->getKeyName($accountId);
        $cache = $this->redis->hGet($key, $orderId);
        if ($cache) {
            return $cache ? json_decode($cache, true) : [];
        }*/
        $result = Db::name('aliexpress_online_order')->where(['order_id' => $orderId,'account_id'=>$accountId])->field('id, gmt_modified,send_good_expire,push_status')->find();
        if($result){
            $data = [
                'gmt_modified' => $result['gmt_modified'],
                'id'  => $result['id'],
                'send_good_expire'  => $result['send_good_expire'],
                'push_status'  => $result['push_status'],
            ];
            return $data;
        }
        return [];

    }

    /*
     *获取订单最后修改时间
     */
    public function setModifiedTime($accountId, $orderId, $data)
    {
        /*$key = $this->getKeyName($accountId);
        $this->redis->sAdd($this->setKey, $accountId);
        if ($data) {
            return $this->redis->hSet($key, $orderId, json_encode($data));
        }*/
        return false;
    }

    private function getKeyName($accountId)
    {
        return $this->key . ':' . $accountId;
    }
}