<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Packing as packingModel;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Packing extends Cache
{
    /** 获取单位种类
     * @return array
     */
    public function getPacking($id = 0)
    {
        if($this->redis->exists('cache:packing')){
            $result = json_decode($this->redis->get('cache:packing'),true);
            if(!empty($result)){
                if(!empty($id)){
                    return isset($result[$id]) ? $result[$id] : [];
                }
                return $result;
            }
        }
        //查表
        $result = packingModel::field('id, title as name, type, weight')->where(['status' => 1])->select();
        $new_list = [];
        foreach ($result as $k => $v) {
            $new_list[$v['id']] = $v;
        }
        $this->redis->set('cache:packing', json_encode($new_list));
        return $result;
    }

    public function getPackageList(){
        $result = $this->getPacking();
        foreach($result as $key => $item){
            if($item['type'] !=2 ){
                unset($result[$key]);
            }
        }
        return $result;
    }
}

