<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Role as RoleModel;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Role extends Cache
{
    /** 获取所有角色信息
     * @return array
     */
    public function getRole($id = 0)
    {
        if($this->redis->exists('cache:Role')){
            $result = json_decode($this->redis->get('cache:Role'),true);
            if ($id) {
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:Role'), true);
        }
        //查表
        $result = RoleModel::select();
        $newResult= [];
        foreach ($result as $k=>$v) {
            $newResult[$v['id']] = $v;
        }
        $this->redis->set('cache:Role',json_encode($newResult));
        if ($id) {
            return isset($newResult[$id]) ? $newResult[$id] : [];
        }
        return $newResult;
    }
    
    /**
     * 删除
     */
    public function delete()
    {
        try {
            $this->redis->del('cache:Role');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
}
