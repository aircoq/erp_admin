<?php
namespace app\common\service;

use think\Exception;

/** 雪花算法-计算订单自增id
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/5
 * Time: 13:59
 */
class Twitter
{
    // 开始时间，固定一个小于当前时间的毫秒
    const start = 1262275200000;   // 2010-01-01 00:00:00
    //平台占的位数
    const workerIdBits = 6;
    //毫秒内自增数点的位数
    const sequenceBits = 0;
    //账号占位数
    const accountBits = 16;
    //要用静态变量
    static $lastTimestamp = -1;
    static $sequence = 0;
    private static $object = NULL;

    /** 初始化
     * @return Twitter
     * @throws Exception
     */
    public static function instance()
    {
        if(is_null(self::$object)){
            //返回对象
            $object = new Twitter();
            self::$object = $object;
        }
        return self::$object;
    }

    /** 生成一个ID
     * @param int $workId  平台
     * @param int $accountId 账号
     * @param $time
     * @return int
     * @throws Exception
     */
    public function nextId($workId = 1023,$accountId = 0,$time = 0)
    {
        //平台ID范围判断
        $maxWorkerId = -1 ^ (-1 << self::workerIdBits);
        if ($workId > $maxWorkerId || $workId < 0) {
            throw new Exception("workerId can't be greater than " . $maxWorkerId . " or less than 0");
        }
        //账号ID范围判断
        $maxAccountId = -1 ^ (-1 << self::accountBits);
        if ($accountId > $maxAccountId || $accountId < 0) {
            throw new Exception("accountId can't be greater than " . $maxAccountId . " or less than 0");
        }
        $timestamp = $this->timeGen($time);
        $lastTimestamp = self::$lastTimestamp;
        //判断时钟是否正常
        if ($timestamp < $lastTimestamp) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds",
                ($lastTimestamp - $timestamp));
        }
        //生成唯一序列
        if ($lastTimestamp == $timestamp) {
            $sequenceMask = -1 ^ (-1 << self::sequenceBits);
            self::$sequence = (self::$sequence + 1) & $sequenceMask;
            if (self::$sequence == 0) {
                $timestamp = $this->tilNextMillis($lastTimestamp);
            }
        } else {
            self::$sequence = 0;
        }
        self::$lastTimestamp = $timestamp;
        //时间毫秒/数据中心ID/机器ID，要左移的位数
        $timestampLeftShift = self::sequenceBits + self::workerIdBits + self::accountBits;
        $workerIdShift = self::sequenceBits + self::accountBits;
        $accountIdShift = self::sequenceBits;
        //组合3段数据返回： 时间戳.平台.账号.序列
        $nextId = (($timestamp - self::start) << $timestampLeftShift) | ($workId << $workerIdShift) | ($accountId << $accountIdShift) | self::$sequence;
        return $nextId;
    }

    /** 获取当前时间毫秒
     * @param int $time
     * @return float
     */
    protected function timeGen($time = 0)
    {
        if (empty($time)) {
            list($t1, $t2) = explode(' ', microtime());
            return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        }
    }

    /** 取下一毫秒
     * @param $lastTimestamp
     * @return float
     */
    protected function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }
}