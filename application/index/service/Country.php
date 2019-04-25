<?php
/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 18-12-19
 * Time: 上午10:03
 */

namespace app\index\service;

use \app\common\model\Country as CountryModel;
use \app\common\model\Zone as ZoneModel;
use think\Db;
use think\Exception;
use app\common\cache\Cache;

class Country
{
    /**
     * @desc 获取所有地区国家信息
     * @param string $zone_code
     * @return array
     */
    public function getCountryByZone($zone_code)
    {
        $zone_en_name = (new ZoneModel())->where('zone_code', $zone_code)->value('zone_en_name');
        if($zone_en_name){
            $where['zone_code']  = ['in', [$zone_en_name, $zone_code]];
        }else{
            $where['zone_code']  = $zone_code;
        }
        $country_code_arr = (new CountryModel())->where($where)->column('country_code');
        return $country_code_arr;
    }

    /**
     * @desc 获取国家所在地区
     * @param string $country_code
     * @return array
     * @throws Exception
     */
    public function getZoneCodeByCountry($country_code)
    {
        $country = Cache::store('country')->getCountry($country_code);
        if(!$country){
            throw new Exception('系统无法识别国家'.$country_code);
        }
        $where['zone_code|zone_en_name'] = $country['zone_code'];
        $zone_code = (new ZoneModel())->where($where)->value('zone_code','');
        return $zone_code;
    }
}