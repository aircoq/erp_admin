<?php
namespace app\api\components;

use app\common\cache\Cache;

/** API 访问次数
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/8
 * Time: 14:46
 */
class ApiVisit
{
    /** 获取api访问次数
     * @param $apiName
     * @return mixed
     */
    public static function getApiVisit($apiName)
    {
        $visitData = Cache::handler()->get($apiName);
        return json_decode($visitData,true);
    }

    /** 校验Api访问次数
     * @param $apiName
     * @param $visitNum
     * @param $prefix
     * @return string
     */
    public static function checkApiVisit($apiName, $visitNum,$prefix = 'api:')
    {
        $visitData = self::getApiVisit($prefix.$apiName);
        if (empty($visitData) || !is_array($visitData)) {
            Cache::handler()->set($prefix.$apiName, json_encode(['num' => 1, 'close' => 0, 'start' => time()]));
            return true;
        }
        if(isset($visitData['time'])){
            $timeOut = time() - $visitData['time'];
        }else{
            $timeOut = 0;
        }
        $timeStart = time() - $visitData['start'];
        $num = $visitData['num'] + 1;
        if ($visitData['close'] == 1) {
            if ($timeOut >= 3600) {
                Cache::handler()->set($prefix.$apiName, json_encode(['num' => 1, 'close' => 0, 'start' => time()]));
                return true;
            }
            ApiPost::error('您访问的速度太频繁，请稍后再访问');
        }
        if ($timeStart < 60) {
            if ($num > $visitNum) {
                ApiPost::error('您访问的速度太频繁，请稍后再访问');
            }
            if ($num == $visitNum) {
                $visitData['num'] = $num;
                $visitData['close'] = 1;
                $visitData['time'] = time();
                Cache::handler()->set($prefix.$apiName, json_encode($visitData));
                return true;
            }
        }
        if ($timeStart >= 60) {
            Cache::handler()->set($prefix.$apiName, json_encode(['num' => 1, 'close' => 0, 'start' => time()]));
            return true;
        }
        $visitData['num'] = $num;
        $visitData['time'] = time();
        Cache::handler()->set($prefix.$apiName, json_encode($visitData));
        return true;
    }
}