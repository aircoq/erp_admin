<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\traits\CachePersist;
//use think\Config;

/**
 * Description of WishListing
 * 提交编辑了资料的在线listing
 * @datetime 2017-5-6  9:16:57
 * @author joy
 */


class WishListing extends Cache{
    use CachePersist;
    protected static $redis;
     
    public function initialize()
    {
       ini_set('default_socket_timeout', -1);
        static::connection();
    }
    
    public static function connection()
    {
        if (isset($GLOBALS['redis'])) {
            $redis = $GLOBALS['redis'];
        } else {
            $redis = new \Redis();
            $GLOBALS['redis'] = $redis;
        }
        
        $cache = config('cache');
        
        $redis->connect($cache['redisPersist']['host'], $cache['redisPersist']['port']);
        return static::$redis = $redis;
    }
    
    /**
     * 向有序集合添加一个或多个成员，或者更新已存在成员的分数
     * @param string $name 有续集键名
     * @param type $workerId 值
     * @param type $mtime 时间/分值
     */
    public static function score($name,$workerId, $mtime)
    {
        static::$redis->zAdd($name, $mtime, $workerId);
    }
    /**
     * 移除有序集合中的一个或多个成员
     * @param type $name 键名
     * @param type $workerId 值
     */
    public static function rem($name,$workerId)
    {
            static::$redis->zRem($name, $workerId);
    }
     /**
     * 迭代器 （迭代队列里的所有元素）
     * @param string $taskName
     */
    
    public static function getRedis($name,$btime, $etime)
    {
        return static::$redis->zRangeByScore($name, $btime, $etime,['withscores']);
    }
    
    
}
