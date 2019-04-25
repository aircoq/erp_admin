<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-29
 * Time: 下午5:44
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;
use app\common\model\RoleAccess;

class RoleFilter extends Cache
{

    protected function getKey($key = '')
    {
        return "hash:roleFilter:$key";
    }

    public function getFilters($roleId, $nodeId)
    {
        $key = $this->getKey($roleId);
        if($this->redis->hExists($key,$nodeId)){
            $filters = $this->redis->hGet($key, $nodeId);
            return !empty($filters) ? unserialize($filters) : [];
        }else{
            $roleAccess = new RoleAccess();
            $filterData = $roleAccess->field('filters')->where(['role_id' => $roleId,'node_id' => $nodeId])->find();
            $filters = !empty($filterData) ? $filterData->toArray()['filters'] : [];
            $filters = json_encode($filters);
            $filters = json_decode($filters,true);
            $this->setFilters($roleId,$nodeId,$filters);
            return $filters;
        }
    }

    public function setFilters($roleId, $nodeId, $filters)
    {
        $key = $this->getKey($roleId);
        $this->redis->hSet($key, $nodeId, serialize($filters));
    }

    public function delFilters($roleId, $nodeId)
    {
        $key = $this->getKey($roleId);
        $this->redis->hDel($key, $nodeId);
    }

    public function delRoleFilters($roleId)
    {
        $key = $this->getKey($roleId);
        $this->redis->delete($key);
    }
}