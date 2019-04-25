<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-6-25
 * Time: 下午1:38
 */

namespace app\index\cache;


use app\common\cache\Cache;

class TaskWorkerLog extends Cache
{
    public function getLogs($workerId, $start = 0, $end = 20)
    {
        $key = $this->getKey($workerId);
        $result = $this->redis->lRange($key, $start, $end);
        if(!is_array($result)){
            $result = [];
        }
        return array_map(function($log){return json_decode($log);}, $result);
    }

    public function addLog($workerId, $workerLog)
    {
        if(!is_string($workerLog)){
            $workerLog = json_encode($workerLog);
        }
        $key = $this->getKey($workerId);
        $this->redis->lPush($key, $workerLog);
    }

    public function remove($workerId)
    {
        $key = $this->getKey($workerId);
        $this->redis->delete($key);
    }
}