<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-22
 * Time: 上午9:57
 */

namespace app\index\cache;


use app\common\cache\Cache;

class Test extends Cache
{
    protected function getKey($key = '')
    {
        return "sets:".$key;
    }
    public function isInvalid($val)
    {
        $key = $this->getKey('ssss');
        $ret = $this->redis->sIsMember($key, $val);
        if($ret){
            $this->redis->sRem($key, $val);
        }
        return $ret;
    }
}