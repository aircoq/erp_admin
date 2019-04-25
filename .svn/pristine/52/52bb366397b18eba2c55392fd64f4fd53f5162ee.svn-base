<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\DataToObjArr;
use app\common\traits\CachePersist;
use think\Config;
use think\Exception;
use think\exception\ErrorException;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/29
 * Time: 10:16
 */
class TaskWorker extends Cache
{
    protected $forceNewConnect = true;
    private static $scheduler = "taskworkers:scheller";
    private static $lasttime = "taskworkers:lasttime";
    private static $queue = "taskworkers:queue";

    private static $global = "taskworkers:global";


    public function pushQueue($task, $params)
    {
        if(!is_string($params)){
            $params = json_encode($params);
        }
        $this->persistRedis->lPush($this->getQueueKey($task), $params);
    }

    public function popQueue($task)
    {
        $val = $this->persistRedis->lPop($this->getQueueKey($task));
        if($val){
            return new DataToObjArr($val);
        }else{
            return false;
        }
    }

    public function getQueueKey($task)
    {
        return static::$queue.":{$task}";
    }

    /**
     * 迭代器 （迭代队列里的所有元素）
     * @param string $taskName
     */
    public function getWorkers($btime, $etime)
    {
        return $this->persistRedis->zRangeByScore(static::$scheduler, $btime, $etime,['withscores'=>true]);
    }


    public function remWorker($workerId)
    {
        return $this->persistRedis->zRem(static::$scheduler, $workerId);
    }

    public function removeall()
    {
        $this->persistRedis->del(static::$scheduler);
    }

    public function scoreWorker($workerId, $mtime)
    {
        return $this->persistRedis->zAdd(static::$scheduler, $mtime, $workerId);
    }

    public function globalWorkers($btime, $etime)
    {
        return $this->persistRedis->zRangeByScore(static::$global, $btime, $etime,['withscores']);
    }

    public function scoreGlobalWorker($workerId, $mtime)
    {
        $this->persistRedis->zAdd(static::$scheduler, $mtime, $workerId);
    }

    public function eventWorkers($event)
    {
        return $this->persistRedis->sMembers("taskworkers:event|{$event}");
    }

    public function eventWorker($event, $param, $taskser)
    {
        $string = json_encode(['params'=>$param, 'tasker'=>$taskser]);
        $this->persistRedis->sAdd("taskworkers:event|{$event}",$string);
    }

    public function getStatus($taskID)
    {
        $key = $this->getKey('status');
        $val = $this->persistRedis->hGet($key, $taskID);
        return $val == 'false' || ! $val ? false : true;
    }

    public function setStatus($taskID, $status)
    {
        $key = $this->getKey('status');
        $this->persistRedis->hSet($key, $taskID, $status);
    }

    public function serverStatus($keyStatus, $keyValue= null, $debug = false)
    {
        $key = $this->getKey('serverStatus');
        $result = json_decode($this->persistRedis->hGet($key, $keyStatus));
        if(!is_null($keyValue)){
            $this->persistRedis->hSet($key, $keyStatus, json_encode($keyValue));
        }
        $result =json_decode($result);
        if($debug){
            echo "serverStatus: $keyStatus -> new:$keyValue;old:$result \n";
        }
        return $result;
    }

    public function taskUsed($used = 0)
    {
        $key = $this->getKey('serverStatus');
        return $this->persistRedis->hIncrBy($key, 'taskUsed',$used);
    }

    public function reset()
    {
        $key = $this->getKey('serverStatus');
        $this->persistRedis->hDel($key, 'taskUsed');
    }

    public function taskMax($max = null)
    {
        $key = $this->getKey('serverStatus');
        if($max){
            $this->persistRedis->hSet($key, 'taskMax', $max);
            return $max;
        }else{
            return $this->persistRedis->hGet($key, 'taskMax');
        }
    }
}