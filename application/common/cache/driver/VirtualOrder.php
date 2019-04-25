<?php
namespace app\common\cache\driver;
use app\common\cache\Cache;
use think\Db;

class VirtualOrder extends Cache
{
    private $_virtual_order = 'cache:virtualOrder:';    //


    public function saveUseCache($orderId,$channel)
    {
        $key = $this->_virtual_order . $orderId . ':' . $channel ;
        return $this->redis->set($key,1,120);
    }

    public function getUserCache($orderId,$channel)
    {
        $key = $this->_virtual_order . $orderId . ':' . $channel ;
        if($this->redis->exists($key)){
            return true;
        }
        return false;
    }

    public function delUserCache($orderId,$channel)
    {
        $key = $this->_virtual_order . $orderId . ':' . $channel ;
        if($this->redis->exists($key)){
            return $this->redis->del($key);
        }
        return false;
    }
    
}