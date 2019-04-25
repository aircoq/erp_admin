<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ShippingLabelNorm as Model;

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2018/08/08
 * Time: 17:45
 */
class LabelNorm extends Cache
{
    /** 获取标签字典
     * @return array
     */
    public function getLabelNorm()
    {
        if($this->redis->exists('cache:labelNorm')){
            $result = json_decode($this->redis->get('cache:labelNorm'),true);
            return $result;
        }
        //查表
        $result = (new Model())->field('id,name,priority,description')->select();
        $this->redis->set('cache:labelNorm',json_encode($result));
        return $result;
    }

}


