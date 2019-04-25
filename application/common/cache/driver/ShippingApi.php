<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/7/14
 * Time: 10:08
 */

namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * 物流接口公用Cache
 * Class ShippingApi
 * @package app\common\cache\driver
 */
class ShippingApi extends Cache
{
    const STARPOST_KEY = 'Starpost';
    const FEIA_COUNTRY_KEY = 'Feia_country';
    /**
     * 星邮设置打印JobId
     * @param $packageNum
     * @param $jobId
     */
    public function starpostSetJobId($packageNum,$jobId)
    {
        $this->persistRedis->hSet(self::STARPOST_KEY,$packageNum,$jobId);
    }

    /**
     * 星邮获取打印jobId
     * @param $packageNum
     * @return bool|string
     */
    public function starpostGetJobId($packageNum)
    {
        if($this->persistRedis->hExists(self::STARPOST_KEY,$packageNum)){
            return $this->persistRedis->hGet(self::STARPOST_KEY,$packageNum);
        }
        return false;
    }

    /**
     * 17Feia设置目的地国家对于ID信息
     * @param $countrys
     */
    public function feiaSetCountry($countrys)
    {
        $this->redis->set(self::FEIA_COUNTRY_KEY,json_encode($countrys));
        $this->redis->expire(self::FEIA_COUNTRY_KEY,86400);
    }

    /**
     * 17Feia获取目的地国家对于ID信息
     * @return bool|mixed
     */
    public function feiaGetCountry()
    {
        if($this->redis->exists(self::FEIA_COUNTRY_KEY)){
            $countrys = $this->redis->get(self::FEIA_COUNTRY_KEY);
            return json_decode($countrys,true);
        }
        return false;
    }
}