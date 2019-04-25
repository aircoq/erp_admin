<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Currency as currencyModel;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Currency extends Cache
{
    const CACHE_KEY = 'cache:Currency';
    const CACHE_DAY_UPDATE_LOCK = 'cache:Currency:lastUpdateTime:lock';

    /** 获取货币种类
     * @return array
     */
    public function getCurrency($code = 0)
    {
        if ($this->redis->exists(self::CACHE_KEY)) {
            if (!empty($code)) {
                $result = json_decode($this->redis->get(self::CACHE_KEY), true);
                return isset($result[$code]) ? $result[$code] : [];
            }
            return json_decode($this->redis->get(self::CACHE_KEY), true);
        }
        $currencyModel = new currencyModel();
        $result = $currencyModel->order('sort asc')->select();
        $new_array = [];
        foreach ($result as $k => $v) {
            $new_array[$v['code']] = $v;
        }
        $this->redis->set(self::CACHE_KEY, json_encode($new_array));
        if (!empty($code)) {
            return $new_array[$code];
        }
        return $new_array;
    }

    /**
     * @desc 不同币种之间的汇率转换，以人民币CNY为基准.
     * @param string $in 原来的币种
     * @param string $out 需要转换成的币种
     * @return double 汇率差
     * @author Jimmy
     * @date 2017-10-19 14:10:11
     */
    public function exchangeCurrency($in, $out)
    {
        try {
            $res = $this->getCurrency();
            return $res[$in]['system_rate'] / $res[$out]['system_rate'];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }

    }

    public function remove()
    {
        $this->redis->del('currency');
        $this->redis->del(self::CACHE_KEY);
    }

    /**
     * @title 每天自动更新仅为1次
     * @param $currentTime
     * @return bool
     * @author starzhan <397041849@qq.com>
     */
    public function setAutoUpdateLock($currentTime)
    {
        $currentDate = date('Ymd', $currentTime);
        $key = self::CACHE_DAY_UPDATE_LOCK . ":" . $currentDate;
        $flag = $this->redis->setnx($key, 1);
        if($flag){
            $this->redis->expireAt($key, strtotime(date('Y-m-d 23:59:59', $currentTime)));
        }
        return $flag;
    }

}