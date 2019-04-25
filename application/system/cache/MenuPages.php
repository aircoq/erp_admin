<?php
namespace app\system\cache;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-26
 * Time: 下午1:57
 */
class MenuPages extends Cache
{
    protected $auto_convert = true;
    protected function getKey($key = '')
    {
        return 'hash:MenuPage';
    }

    public function getPages($userId)
    {
        return $this->redis->hGet($this->getKey(), $userId);
    }

    public function setPages($userId, $pages)
    {
        $this->redis->hSet($this->getKey(), $userId, json_encode($pages));
    }

    public function delPages($userId)
    {
        $this->redis->hDel($this->getKey(), $userId);
    }

    public function delAll()
    {
        $this->redis->del($this->getKey());
    }
}