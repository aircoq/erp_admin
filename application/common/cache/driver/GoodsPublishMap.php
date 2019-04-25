<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-9
 * Time: 上午11:22
 */

namespace app\common\cache\driver;
use app\common\cache\Cache;
use app\goods\service\GoodsPublishMapService;
class GoodsPublishMap extends Cache{

    protected static $redis;
    protected const KEY="published:";
    protected const CHANNEL= GoodsPublishMapService::CHANNEL;
    public function __construct()
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
        if(isset($cache['redisPersist']['password']))
        {
            $redis->auth($cache['redisPersist']['password']);
        }
        return static::$redis = $redis;
    }

    /**
     * 设置刊登缓存
     * @param $product_id
     * @param $data
     */
    public static function setPublishCache($channel_id,$spu,$data)
    {
        $channel=self::CHANNEL[$channel_id];

        static::$redis->hSet(self::KEY.$channel,$spu,json_encode($data));
    }

    /**
     * 获取刊登缓存
     * @param $product_id
     * @return mixed|string
     */
    public static function getPublishCache($channel_id,$spu)
    {
        $channel=self::CHANNEL[$channel_id];
        $cache = static::$redis->hGet(self::KEY.$channel,$spu);
        if($cache)
        {
            return json_decode($cache,true);
        }else{
            return '';
        }
    }
}