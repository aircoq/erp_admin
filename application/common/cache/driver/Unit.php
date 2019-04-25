<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Unit as unitModel;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Unit extends Cache
{
    /** 获取单位种类
     * @return array
     */
    public function getUnit()
    {
        if($this->redis->exists('cache:unit')){
            $result = json_decode($this->redis->get('cache:unit'),true);
            return $result;
        }
        //查表
        $result = unitModel::field('id,name')->select();
        $this->redis->set('cache:unit',json_encode($result));
        return $result;
    }
}

