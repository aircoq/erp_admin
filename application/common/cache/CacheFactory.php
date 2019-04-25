<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-22
 * Time: 上午10:59
 */

namespace app\common\cache;


use app\common\service\DataToObjArr;
use think\Exception;

class CacheFactory{
    private $object = null;
    private $autoConver = false;
    public function __construct($class)
    {
        $this->object = Cache::getObject($class);
        $this->autoConver = $this->getCacheOption('auto_convert');
    }

    protected function getCacheOption($key)
    {
        return $this->object->getOption($key);
    }

    public function __call($call, $argvs)
    {
        if(method_exists($this->object, $call)){
            $ret = call_user_func_array([$this->object, $call], $argvs);
            if($ret && $this->autoConver){
                $ret = new DataToObjArr($ret);
            }
            return $ret;
        }else{
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
            $class = get_called_class();
            throw new Exception("{$debug[0]['file']} {$debug[0]['line']} call cache class:{$class} not defined method:{$call}");
        }
    }

    public function __isset($propery)
    {
        switch ($propery){
            case 'persistRedis':
                return true;
            case 'redis':
                return true;
            default:
                return false;
        }
    }

    public function __get($propery)
    {
        switch ($propery){
            case 'persistRedis':
                return $this->object->$propery;
            case 'redis':
                return $this->object->$propery;
            default:
                return false;
        }
    }
}