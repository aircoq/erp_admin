<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-7
 * Time: 上午9:40
 */

namespace app\common\cache;


use app\common\exception\CacheModelException;

class CacheModel  implements \JsonSerializable, \ArrayAccess
{
    /**
     * @var \Redis
     */
    private static $redis;
    protected static $defaultVal = '';
    private $wheres = [];
    private $orders = [];
    protected static $fields = [];
    protected $allowFields = [];
    protected static $systemFields = ['id','created_at', 'updated_at'];
    private $datas = [];
    private $changeFields = [];

    private static function initConnection()
    {
    	if(get_called_class() == 'app\common\cache\TaskWorker' && self::$redis){
    		self::$redis->close();
    		self::$redis = null;//任务管理里，得用持久化连接。
    	}
        if(!self::$redis){
            $config = \think\Config::get("cache.redisPersist");
            if(get_called_class() == 'app\common\cache\RBAC'){
                //$config = \think\Config::get("cache.default");//改非持久化
            	self::$redis = \think\cache\driver\Redisrw::getInstance('cache.default');
            	return true;
            }
            self::$redis = new \Redis();
            self::$redis->connect($config['host'], $config['port']);
            if(isset($config['password'])){
                self::$redis->auth($config['password']);
            }
        }
    }

    public final function __construct($data = [])
    {
        self::initConnection();
        $this->allowFields = static::getFields();
        $this->datas = $data;
    }

    private static function getFields()
    {
        return array_merge(static::$fields, static::$systemFields);
    }

    public final function getKey($key = '')
    {
        return static::class;
    }

    protected function getPk()
    {
        return $this->id;
    }

    public static function clear()
    {
        self::initConnection();
        self::$redis->del(static::class);
    }

    private function getCache($id = null)
    {
        $id = $id ?: $this->getPk();
        if($id){
            $data = self::$redis->hGet($this->getKey(), $id);
            if($data){
                return json_decode($data, true);
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function find($id)
    {
        $cache = $this->getCache($id);
        if($cache){
            $this->datas = $cache;
        }
        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public static function get($id, $not = '')
    {
        self::initConnection();
        $data = self::$redis->hGet(static::class, $id);
        if($data){
            $json = json_decode($data, true);
            if($json){
                return new static($json);
            }else{
                $class = static::class;
                echo "get:{$id} {$class} \n";
                dump($data);
                dump(self::$redis);
            }

        }else{
            return null;
        }
    }

    public static function transplantCache(\Redis $sredis, \Redis $dredis)
    {
        $datas = $sredis->hGetAll(static::class);
        $dredis->delete(static::class);
        foreach ($datas as $key => $data){
            $dredis->hSet(static::class, $key, $data);
        }
    }
    
    public function lock($key)
    {
    	return self::$redis->set('lock:' . $key, time(), ['nx', 'ex' => 5]);
    }
    
    public function unlock($key)
    {
    	self::$redis->del('lock:' . $key);
    }

    private function genPk()
    {	
    	$lkey = 'generate_id';
    	if($this->lock($lkey)){
    		$maxkey = 0;
    		$data = self::$redis->hKeys(static::class);
    		rsort($data, SORT_NUMERIC);
    		$maxkey = $data[0] ?? 0;
    		$id = self::$redis->hIncrBy(self::class, static::class, 1);
    		if($id < $maxkey){
    			$id = $maxkey + 1;
    			self::$redis->hSet(self::class, static::class, $id);
    		}
    		$this->unlock($lkey);
    	}else{
    		sleep(1);
    		return $this->genPk();
    	}
    	return $id;
    }

    private function setCache($data)
    {
        if(!($pk = $this->getPk())){
            $pk = $this->genPk();
            $this->datas['id'] = $pk;
            $data['id'] = $pk;
        }
        $data = json_encode($data);
        self::$redis->hSet($this->getKey(), $pk, $data);
    }

    public function where($field, $value, $op = '===')
    {
        $this->wheres[$field] = ['op'=> $op, 'val' => $value];
    }

    public function setWheres($wheres)
    {
        $this->wheres = $wheres;
    }

    /**
     * @param null $call
     * @return array
     * @throws CacheModelException
     */
    public static function all($call = null, $orders = [])
    {
        self::initConnection();
        $iter = null;
        $all = [];
        while ($ret = self::$redis->hScan(static::class, $iter, '', 10)){
            $all = array_merge($all, $ret);
        }
        $result = [];
        foreach ($all as $item){
            $item = json_decode($item, true);
            $one = new static($item);
            if(is_callable($call)){
                $ret = $call($one);
                $type = value_type($ret);
                switch ($type){
                    case 'boolean':
                        if($ret){
                            $result[] = $one;
                        }
                        break;
                    case 'null':
                        if($one->parseWhere(true)){
                            $result[] = $one;
                        };
                        break;
                    default:
                        throw new CacheModelException(new static(), "all call return ({$type}) not support");
                }
            }else{
                $result[] = $one;
            }
        }
        $order_function = function($m1, $m2) use($orders){
            foreach ($orders as $field => $order){
                if($m1->$field > $m2->$field){
                    return true;
                }else{
                    return false;
                }
            }
            return true;
        };
        usort($result, $order_function);
        return $result;
    }

    public function order($field, $order = 'desc')
    {
        $this->orders[$field] = $order;
    }

    private function parseWhere($needClearWhere)
    {
        $result = true;
        foreach ($this->wheres as $field => $where){
            $op = $where['op'];
            $val= $where['val'];
            switch ($op){
                case '==':
                case '===':
                    if($this->$field !== $val){
                        $result = false;
                        goto foreachEnd;
                    }
                    break;
                case '<>':
                    if($this->$field === $val){
                        $result = false;
                        goto foreachEnd;
                    }
                    break;
                case 'in':
                    if(!in_array($this->$field, $val)){
                        $result = false;
                        goto foreachEnd;
                    }
                    break;
                case '<=':
                    if($this->$field > $val){
                        $result = false;
                        goto foreachEnd;
                    }
                    break;
                case '>=':
                    if($this->$field < $val){
                        $result = false;
                        goto foreachEnd;
                    }
                    break;
            }
        }
        foreachEnd:
        if($needClearWhere){
            $this->setWheres([]);
        }
        return $result;
    }

    public function select($where = [])
    {
        $wheres = array_merge($this->wheres, $where);
        $this->setWheres([]);
        if(count($wheres)){
            $call = function(CacheModel $model)use($wheres){
                $model->setWheres($wheres);
                if(!$model->parseWhere(true)){
                    return false;
                }else{
                    return true;
                }
            };
        }else{
            $call = null;
        }
        return static::all($call);
    }

    public function __set($key, $val)
    {
        if(!in_array($key, static::getFields())){
            throw new CacheModelException($this, 'set field '.$key.' ,but not define this field');
        }
        if(!in_array($key, $this->allowFields)){
            throw new CacheModelException($this, 'set field '.$key.' ,but not allow this field');
        }
        $callSet = "set".firstUpper($key)."Attr";
        if(is_callable([$this, $callSet])){
            $val = $this->$callSet($val);
        }
        $this->datas[$key] = $val;
        $this->changeFields[$key] = $val;
    }

    public function __get($key)
    {
        if(!in_array($key, static::getFields())){
            throw new CacheModelException($this, $key." not define this key");
        }
        if(!in_array($key, $this->allowFields)){
            throw new CacheModelException($this, $key." not allow get this key");
        }
        if(!is_array($this->datas)){
            debug_print_backtrace(0, 4);
            throw new CacheModelException($this, $key." not datas");
        }
        if(array_key_exists($key, $this->datas)){
            $val = $this->datas[$key];
            $callSet = "get".firstUpper($key)."Attr";
            if(is_callable([$this, $callSet])){
                $val = $this->$callSet($val);
            }
            return $val;
        }else{
            return $this->getDefault($key);
        }
    }

    protected function defaultCreated_at()
    {
        return now();
    }

    protected function defaultUpdated_at()
    {
        return now();
    }

    protected function defaultId()
    {
        //$id = self::$redis->hIncrBy(self::class, static::class, 1);
        $id = $this->genPk();
        $this->datas['id'] = $id;
        return $id;
    }

    private function getDefault($key)
    {
        $callback = 'default'.firstUpper($key);
        if(is_callable([$this, $callback])){
            return $this->$callback();
        }
        return static::$defaultVal;
    }

    public function field($field)
    {
        if(is_string($field)){
            preg_match_all("/([\w]+)/i", $field, $match);
            $field = $match[0];
        }
        $field = array_unique(array_merge($field, ['id']));
        $this->allowFields = $field;
    }

    public function delete()
    {
        if(self::$redis->hDel($this->getKey(), $this->getPk())){
            $this->datas = [];
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        $cache = $this->getCache();
        if(!$cache){
            $this->setCache($this->datas);
            return true;
        }else{
            $need = false;
            foreach (static::getFields() as $field){
                if(!array_key_exists($field, $this->datas)){
                    $this->datas[$field] = $this->getDefault($field);
                }
                if(!array_key_exists($field, $cache)){
                    $cache[$field] = $this->getDefault($field);
                }
            }
            //只保存这次改动的字段，没手动改动的字段还是用缓存内的
            if(count($this->changeFields)){
                $need = true;
                foreach ($this->changeFields as $field => $val){
                    $cache[$field] = $val;
                }
            }

            $cache['updated_at'] = now();
            $this->datas = $cache;
            if($need){
                $this->setCache($cache);
                return true;
            }else{
                return false;
            }
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->datas[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->datas[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function __toString()
    {
        return json_encode($this);
    }

    function jsonSerialize()
    {
        $result = [];
        foreach ($this->allowFields as $field){
            try{
                $result[$field] = $this->$field;
            }catch (CacheModelException $exception){
                echo "$field fail:\n";
                dump($this->datas);
            }

        }
        return $result;
    }
}