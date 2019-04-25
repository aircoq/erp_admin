<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-1
 * Time: 下午2:01
 */

namespace app\common\service;
use app\publish\queue\EbayPublishItemQueuer;
use swoole\SwooleTasker;


/**
 * @doc 元素唯一性队列，保存同一时刻，队列不会存在相同元素
 */
class UniqueQueuer extends BaseQueuer
{

    /**
     * @param $params
     * @param $timer int 定时时间|小于当前时间认为n后，会自动加当前时间
     */
    public function push($params = null, $timer = null)
    {
        $result = false;
        if(is_numeric($params) || is_string($params)) $params = trim($params);
        if(!empty($params)){
            if($this->lock()){
                $params = serialize($params);
                if(($this->cache->memberCount($this->key, $params) <= 0)){
                    $timer = $this->getTimer($timer);
                    if(!$timer){
                        $this->cache->pushQueue($this->key, $params);
                    }else{
                        $this->cache->pushTimer($this->key, $params, $timer);
                    }
                    $result = true;
                }
                $this->unlock();
                return $result;
            }else{
                sleep(1);//推迟1s
                return $this->push($params, $timer);
            }
        }else{
            return $result;
        }
    }

    private function lock()
    {
        return $this->cache->lock($this->key);
    }

    private function unlock()
    {
        $this->cache->unlock($this->key);
    }

    public function pop(){
        if($params = $this->cache->popQueue($this->key)){
            return unserialize($params);
        }else{
            return false;
        }
    }

    /**
     * @param $params
     */
    public function remove($params)
    {
        if(is_numeric($params) || is_string($params)) $params = trim($params);
        $params = serialize($params);
        $result = $this->cache->memberRemove($this->key, $params);
        if($result){
            $this->cache->removeTimer($this->key, $params);
        }
        return $result;
    }

    /**
     * @param $params
     * @return bool
     */
    public function exist($params)
    {
        if(is_numeric($params) || is_string($params)) $params = trim($params);
        $params = serialize($params);
        return !!$this->cache->memberCount($this->key, $params);
    }

    /**
     * @return array
     */
    public function lists()
    {
        $members = $this->cache->members($this->key);
        return array_map(function($params){return unserialize($params);}, $members);

    }

    /**
     * @return int
     */
    public function queueLength()
    {
        return $this->cache->queueCount($this->key);
    }
}