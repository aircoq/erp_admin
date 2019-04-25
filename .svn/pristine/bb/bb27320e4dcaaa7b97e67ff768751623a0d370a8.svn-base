<?php

/**
 * Description of RedisListing
 * @datetime 2017-5-27  9:14:33
 * @author joy
 */

namespace app\listing\service;
 
class RedisListing {
    private $redis;
    public function __construct() 
    {
        $this->redis = \app\common\cache\Cache::handler(true); 
        return $this->redis;
    }
    /**
     * 统计缓存总数
     * @param string $name
     * @param int $min
     * @param int $max
     */
    public  function myZRangeByScore($name,$min,$max)      
    {
        return $this->redis->zRangeByScore($name,$min,$max,['withscores']);
    }
    /**
     * 返回有序集中指定区间内的成员，通过索引，分数从高到底
     * @param type $name
     * @param type $min
     * @param type $max
     * @return type
     * ZREVRANGEBYSCORE key max min [WITHSCORES] 
     */
    public  function myZRevrangeByScore($name,$min,$max)
    {
        return $this->redis->zRevrangeByScore($name,$min,$max,['withscores'] );
    }
    /**
     * 移除有序集合中的一个或多个成员
     * @param type $name
     * @param type $pid
     */
    public function myZRem($name,$pid)
    {
        $this->redis->zRem($name,$pid);
    }
    /**
     * 向集合添加一个或多个成员
     * @param type $name
     * @param type $pid
     */
    public function mySadd($name,$pid)
    {
        $this->redis->sAdd($name,$pid);
    }
    /**
     * 返回集合中的所有成员
     * @param type $name
     * @return type
     */
    public  function mySmembers($name)
    {
        return $this->redis->sMembers($name);
    }
    /**
     *  添加有续集
     * @param string $name
     * @param int $score
     * @param string $pid
     */
    public  function myZdd($name,$score,$pid)
    {
        $this->redis->zAdd($name,$score,$pid);
    }

    /**
     * 分页
     * @param array $list
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public  function page(array $list, $page, $pageSize)
    {
        $j = 0;
        $new_array = [];
        $start = !empty($page) ? intval($page) - 1 : 0;
        $start = intval($start) * intval($pageSize);
        $end = intval($start) + intval($pageSize);
        foreach ($list as $k => $v) {
            $j++;
            if ($start == 0) {
                if ($j >= $start && $j < ($end + 1)) {
                    array_push($new_array, $v);
                }
            } else {
                if ($j > $start && $j < ($end + 1)) {
                    array_push($new_array, $v);
                }
            }
        }
        return $new_array;
    }
}
