<?php
namespace app\common\cache\driver;
use app\common\cache\Cache;
use app\common\model\Country as CountryModel;
use app\common\model\Zone as ZoneModel;
use think\Db;

class Country extends Cache
{
    /** 获取所有地址国家信息
     * @param int $id
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getCountry($id = 0)
    {
        $key = 'hash:Country';
        if ($this->redis->exists($key)) {
            if (!empty($id)) {
                $result = json_decode($this->redis->hGet($key, $id),true);
                return $result ? $result : [];
            }
            $lists = $this->redis->hGetAll($key);
            foreach($lists as $code => $list) {
                $lists[$code] = json_decode($list, true);
            }
            return $lists;
        }
        $countryModel = new CountryModel();
        $result = $countryModel->select();
        foreach($result as $val){
            $this->redis->hSet($key, $val['country_code'], json_encode($val->toArray()));
            /*if (!empty($val['country_alias1'])) {
                $this->redis->hSet($key, $val['country_alias1'], json_encode($val->toArray()));
            }*/
        }
        unset($result);
        return $this->getCountry($id);
    }
    
    /** 获取所有地区大类信息
     * @param int $zone_code
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getZone($zone_code = '')
    {
        if ($this->redis->exists('cache:Zone')) {
            if (!empty($zone_code)) {
                $result = json_decode($this->redis->get('cache:Zone'),true);
                return isset($result[$zone_code]) ? $result[$zone_code] : [];
            }
            return json_decode($this->redis->get('cache:Zone'),true);
        }
        $zoneModel = new ZoneModel();
        $result = $zoneModel->field('zone_code,zone_en_name,zone_cn_name')->select();
        $new_array = [];
        foreach($result as $v){
            $new_array[$v['zone_code']] = $v;
        }
        $this->redis->set('cache:Zone', json_encode($new_array));
        if(!empty($zone_code)){
            return isset($new_array[$zone_code]) ? $new_array[$zone_code] : [];
        }
        return $new_array;
    }

}