<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-31
 * Time: 上午10:43
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class QueuerLog extends Cache
{
    protected $db = 1;
    protected function getKey($key = '')
    {
        return 'queue:logs:'.$key;
    }

    public function recordLog($queuer, $element, $type, $result)
    {
        $queuer = $this->getKey($queuer);
        $log = json_encode(['e'=>$element,'c'=>$type,'r'=>$result,'t'=>time()]);
        $this->persistRedis->lPushRemove($queuer, $log, 200);
    }

    public function getRecordLog($queuer, $start=0, $end = 20)
    {
        $queuer = $this->getKey($queuer);
        $result = $this->persistRedis->lRange($queuer, $start, $end);
        if(!is_array($result)){
            $result = [];
        }
        return array_map(function($log){return json_decode($log, true);}, $result);
    }

    public function delete($queuer)
    {
        $queuer = $this->getKey($queuer);
        $this->persistRedis->delete($queuer);
    }

    /**
     * @param $key
     * @param $cond
     * @return $log | null
     */
    public function popLog($key, \Closure $cond)
    {
        if($json = $this->persistRedis->rPop($key)){
            $log = json_decode($json, true);
            if($cond($log)){
                return $log;
            }else{
                //压回
                $this->persistRedis->rPush($key, $json);
                return null;
            }
        }else{
            return null;
        }
    }

    public function getKeys()
    {
        return $this->persistRedis->keys($this->getKey('*'));
    }
}