<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Brand as brandModel;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Brand extends Cache
{
    /** 获取标签字典
     * @return array
     */
    public function getBrand()
    {
        if($this->redis->exists('cache:brand')){
            $result = json_decode($this->redis->get('cache:brand'),true);
            return $result;
        }
        //查表
        $result = brandModel::field('id,name')->select();
        $this->redis->set('cache:brand',json_encode($result));
        return $result;
    }
    
    /**
     * 获取品牌风险信息
     */
    public function getTort()
    {
        $result = [
            ['id' => 1, 'name' => '非仿牌'],
            ['id' => 2, 'name' => '仿牌'],
            ['id' => 3, 'name' => '灰色产品']
        ];
        return $result;
    }
}


