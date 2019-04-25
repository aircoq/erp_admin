<?php
namespace app\index\cache;
use app\common\cache\Cache;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-19
 * Time: 下午5:53
 */
class Role extends Cache
{
    const PAGE="ROLE_PAGES";

    protected function getKey($key = '')
    {
        return "hash:Role".$key;
    }
    protected $auto_convert = true;

    public function setPages($roleId, $pages)
    {
        $this->redis->hSet($this->getKey('pages'), $roleId, json_encode($pages));
    }

    public function getPages($roleId)
    {
        return $this->redis->hGet($this->getKey('pages'), $roleId);
    }

    public function getPermission($roleId)
    {
        $key = $this->getKey('permission');
        $permission = $this->redis->hGet($key, $roleId);
        if($permission === false){
            return [];
        }else{
            return $permission;
        }
    }

    public function setPermission($roleId, $apis)
    {
        $key = $this->getKey('permission');
        if(!is_string($apis)){
            $apis = json_encode($apis);
        }
        $this->redis->hSet($key, $roleId, $apis);
    }

    public function delPermission($roleId)
    {
        $key = $this->getKey('permission');
        $this->redis->hDel($key, $roleId);
    }

    public function clearPermission()
    {
        $this->redis->del($this->getKey('permission'));
    }

    public function clearPages()
    {
        $this->redis->del($this->getKey('pages'));
    }

}