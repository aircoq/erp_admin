<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-2
 * Time: 下午3:08
 */

namespace app\common\service;


use app\common\cache\Cache;
// use app\common\model\Queue as QueueModel;
// use think\Config;

abstract class BaseQueuer
{
    protected $key;

    protected $timer = null;

    /**
     * @var $cache \app\common\cache\driver\Queuer
     */
    protected $cache;
    protected static $pushTable = [];
    /**
     * BaseQueuer constructor.
     * @param $key string | SwooleQueueJob::class | xxxx
     */
    public final function __construct($key)
    {
        $this->key = $key;
        $this->cache = Cache::store('queuer');
        if(empty(self::$pushTable[$key])){
        	$this->cache->addQueue($key, static::class);
//         	if(class_exists($key) && is_subclass_of($key, SwooleQueueJob::class)){
//         		//$this->cache->addSwooleWorker($key, forward_static_call([$key, 'getPriority']));
//         		$hosttype = null;
//         		$current = Config::get("swoole.current_hosttype") ?: "common";
//         		$host_types = Config::get('swoole_hosttype') ?: (Config::load(APP_PATH .'common_config.php')['swoole_hosttype'] ?? $this->cache->getQueueHosttypeKey());
//         		foreach ($host_types as $type => $val){
//         			 if($this->cache->checkQueueHostType($key, $type)){
//         			 	$hosttype = $type;
//         			 	break;
//         			 }
//         		}
//         		if(! $hosttype){
//         			$hosttype = QueueModel::get(['queue_class' => class2path($key)])->host_type ?? $current;
//         			$result = $this->cache->addQueueHostType($key, $hosttype, forward_static_call([$key, 'getPriority']));
//         		}
//         		self::$pushTable[$key] = $hosttype;
//         	}
        	self::$pushTable[$key] = 1;
        }
    }


    public abstract function push($params, $timer=null);

    /**
     * @doc 不要裸pop,请用popCall.
     */
    public abstract function pop();

    /**
     * @doc 裸用pop时，请用这个。
     * @param callable $execute($params)
     * @return bool 成功true,失败false|throw
     */
    public function popCall(callable $execute){
        if($params = $this->pop()){
            $this->cache->addWaitQueue($this->key, $params);
            $execute($params);
            $this->cache->remWaitQueue($this->key, $params);
            return true;
        }else{
            return false;
        }

    }

    public function timer($timer)
    {
        $this->timer = $timer;
    }

    protected function getTimer($timer)
    {
        $timer = $timer ?: $this->timer;
        if($timer){
            $now = time();
            if($timer >= $now){
                return $timer;
            }else{
                if($timer > (365 * 3600 * 24)){
                    return $timer;
                }else{
                    return $now + $timer;
                }
            }
        }else{
            return null;
        }
    }

    public abstract function exist($params);
    public abstract function remove($params);

    public abstract function lists();

    public abstract function queueLength();
}
