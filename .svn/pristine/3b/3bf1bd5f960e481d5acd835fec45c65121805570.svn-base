<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-1
 * Time: 下午2:01
 */

namespace app\common\service;


/**
 * @doc 普通，一般的队列
 */
class CommonQueuer extends BaseQueuer
{
    public function push($params=null, $timer = null)
    {
        if(is_numeric($params) || is_string($params)) $params = trim($params);
        if(!empty($params)){
            $params = serialize($params);
            if($timer = $this->getTimer($timer)){
                $this->cache->pushTimer($this->key, $params, $timer);
            }else{
                $this->cache->pushQueue($this->key, $params);
            }
        }
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
     * @return bool
     */
    public  function exist($params)
    {
        if(is_numeric($params) || is_string($params)) $params = trim($params);
        $params = serialize($params);
        return !!$this->cache->memberCount($this->key, $params);
    }

    /**
     * @param $params
     * @return bool
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