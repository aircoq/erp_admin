<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\Common;
use think\Exception;
use think\Request;

class Lock extends Cache
{
    /** @var string xsd缓存key */
    private $hashPrefix = 'lock:';
    private $baseKey = '';
    private $lockParamKey = '';

    public function __construct()
    {
        parent::__construct();
        $request = Request::instance();
        $controller = $request->controller();
        $module = $request->module();
        $action = $request->action();
        $uid = Common::getUserInfo($request)['user_id'];
        if (empty($controller)) {
            $controller = 'controller';
        }
        if (empty($module)) {
            $module = 'module';
        }
        if (empty($action)) {
            $action = 'action';
        }
        if (empty($uid)) {
            $uid = 0;
        }
        $this->baseKey = $this->hashPrefix. $module. ':'. $controller. ':'. $action. ':uid-'. $uid;
    }


    /**
     * 拿取当前加锁的KEY；
     * @param $params
     * @return string
     */
    public function getLockKey($params)
    {
        if (empty($params)) {
            return $this->baseKey;
        }
        $md5key = md5((string)json_encode($params));
        if ($md5key) {
            return $this->baseKey. ':'. $md5key;
        } else {
            return $this->baseKey;
        }
    }


    /**
     * 拿取当前加锁的KEY；
     * @param $params
     * @return string
     */
    public function getLockParamKey($params, bool $md5bol = true)
    {
        if (empty($params)) {
            throw new Exception('加锁参数为空，加锁失败');
        }
        if ($md5bol === false && !is_string($params)) {
            throw new Exception('加锁参数为空，当使用显示参数加索时，参数必需是一个字符串');
        }
        $this->lockParamKey = $this->hashPrefix. 'lock-param:';
        //显示加锁；
        if ($md5bol === false) {
            return $this->lockParamKey. $params;
        }

        //以下为隐式加锁；
        $md5key = md5((string)json_encode($params));
        if ($md5key) {
            return $this->lockParamKey. ':'. $md5key;
        } else {
            throw new Exception('加锁参数不能生成MD5值，加锁失败');
        }
    }

    /**
     * 加锁
     * 最大尝试加锁次数等于 锁时间/等时间；
     * @param array $params 加锁的参数；
     * @param int $locktime 加锁的最长时间，默认锁20次;
     * @param int $wait 每次尝试的等待时间；
     * @return bool
     */
    public function lock($params = [], $locktime = 20, $wait = 50000) {
        $key = $this->getLockKey($params);
        $max = ceil($locktime * 1000000 / $wait);
        while($max) {
            if ($this->redis->set($key, 1, ['nx', 'ex' => $locktime])) {
                return true;
            }
            $max--;
            usleep($wait);
        }
        return false;
    }


    /**
     * 执行完成解锁；
     * @param array $params
     * @return bool
     */
    public function unlock($params = [])
    {
        $key = $this->getLockKey($params);
        $this->redis->del($key);
        return true;
    }

    /**
     * 唯一运行锁；
     * @param array $params 加锁的参数；
     * @param int $locktime 加锁的最长时间，默认锁20次;
     * @return bool
     */
    public function uniqueLock($params = [], $locktime = 30) {
        $key = $this->getLockKey($params);
        if ($this->redis->set($key, 1, ['nx', 'ex' => $locktime])) {
            return true;
        }
        return false;
    }

    /**
     * 对参数进行加锁
     * 最大尝试加锁次数等于 锁时间/等时间；
     * @param array $params 加锁的参数；
     * @param int $locktime 加锁的最长时间，默认锁20次;
     * @param int $wait 每次尝试的等待时间；
     * @return bool
     */
    public function lockParams($params, $md5bol = true, $locktime = 20, $wait = 50000) {
        $key = $this->getLockParamKey($params, $md5bol);
        $max = ceil($locktime * 1000000 / $wait);
        while($max) {
            if ($this->redis->set($key, 1, ['nx', 'ex' => $locktime])) {
                return true;
            }
            $max--;
            usleep($wait);
        }
        return false;
    }


    /**
     * 执行完成解锁；对参数进行解锁
     * @param array $params
     * @return bool
     */
    public function unlockParams($params, $md5bol = true)
    {
        $key = $this->getLockParamKey($params, $md5bol);
        $this->redis->del($key);
        return true;
    }

}
