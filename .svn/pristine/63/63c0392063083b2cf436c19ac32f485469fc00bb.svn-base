<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/22
 * Time: 10:06
 */
namespace app\publish\cache;
use app\common\cache\Cache;

class QueueCache
{
	protected static $persistRedis=null;
	public function __construct()
	{
		if(empty(self::$persistRedis))
		{
			self::$persistRedis=Cache::handler(true);
		}
	}

	/**
	 * @param $key 键名
	 * @param $value 键值
	 * @param int $count 数量，0删除所有
	 */
	public function remove($queuer, $value, $count=0)
	{
		$key = "sets:queue|exist|$queuer";
		$res = self::$persistRedis->lRem($key, serialize($value), $count)?:0;
		//删除成功
		if($res)
		{
			$this->srem($queuer,$value);
			$this->queueCount($queuer, -1);
		}
		return $res;
	}
	public function queueCount($queuer, $incr = 0)
	{
		return self::$persistRedis->hIncrBy("hash:queues|count", $queuer, $incr) ?: 0;
	}

	public function srem($queuer, $params)
	{
		$key = "sets:queue|exist|$queuer";
		return self::$persistRedis->sRem($key, serialize($params));
	}
}