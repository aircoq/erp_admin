<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Tag as tagModel;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Tag extends Cache
{
    /** 获取标签字典
     * @return array
     */
    public function getTag()
    {
        if($this->redis->exists('cache:tag')){
            $result = json_decode($this->redis->get('cache:tag'),true);
            return $result;
        }
        //查表
        $result = tagModel::field('id,name')->select();
        $this->redis->set('cache:tag',json_encode($result));
        return $result;
    }
}
