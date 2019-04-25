<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-14
 * Time: 下午4:04
 */

namespace app\index\cache;



use app\common\cache\Cache;

class Swoole extends Cache
{
    public function modifyFile($file, $notifyRs)
    {
        $key = $this->getKey('notifyFiles');
        return $this->redis->hSet($key, $file, $notifyRs);
    }

    public function modifyFileCount($remove = true)
    {
        $key = $this->getKey('notifyFiles');
        $count = $this->redis->zSize($key) ?: 0;
        if($remove){
            $this->redis->delete($key);
        }
        return $count;
    }
}