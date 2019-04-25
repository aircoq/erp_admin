<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-3
 * Time: 上午9:44
 */

namespace app\index\service;


use app\common\cache\Cache;
use app\common\cache\RBAC;
use app\common\model\RoleUser;
use app\system\server\Menu;
use erp\AbsServer;
use app\common\exception\JsonErrorException;
use app\common\model\McaNode;
use app\common\model\RoleAccess;
use app\common\model\Role as RoleModel;
use erp\ErpModel;
use erp\ErpQuery;
use think\Db;
use think\db\Query;

class Role
{
    /**
     * @var \app\index\cache\Role
     */
    protected $cache;

    /**
     * @var \app\common\cache\driver\RoleFilter
     */
    protected $roleFillter;

    public function __construct()
    {
        $this->cache = Cache::moduleStore('role');
        $this->roleFillter = Cache::store('roleFilter');
    }

    public function getRole($roleId)
    {
        return RoleModel::get($roleId);
    }

    public static function refreshAllPermissions()
    {
        $roles = RoleModel::all();
        $server = new static();
        foreach ($roles as $role) {
            $server->refreshAccess($role->id);
        }
    }

    public function getPermission($roleId)
    {
        $permission = $this->cache->getPermission($roleId);
        if (empty($permission)) {
            if ($rbac = $this->getRbac($roleId)) {
                $permission = array_foldl($rbac->visits, [],
                    function ($node, &$retapis) {
                        $apis = McaNode::apis($node);
                        $retapis = array_merge_plus($retapis, $apis);
                    });
                $this->cache->setPermission($roleId, $permission);
            } else {
                $permission = [];
            }
        }
        return $permission;
    }

    public function clearRbac()
    {
        RBAC::clear();
    }


    public function updateRoleAccess($roleId)
    {
        $visits = RoleAccess::getNodeIds($roleId);
        if ($rbac = RBAC::get($roleId)) {
            $rbac->visits = $visits;
        } else {
            $rbac = new RBAC();
            $rbac->id = $roleId;
            $rbac->visits = $visits;
        }
        $rbac->relates = array_foldl($visits, [], function ($nodeid, &$ret) {
            $apis = McaNode::apis($nodeid);
            $ret = array_merge($ret, $apis);
        });
        $rbac->relates = array_map(function ($relate) {
            return McaNode::relate2route($relate);
        }, $rbac->relates);
        $rbac->save();
    }

    public function removeOverdueAccess($roleId)
    {
        $nodes = RoleAccess::getNodeIds($roleId);
        $mcas = McaNode::all(function(ErpQuery $query){$query->field('id');});
        $mcas = array_map(function($mca){return $mca->id;}, $mcas);
        $deletes = array_diff($nodes, $mcas);
        Cache::handler()->hSet('hash:overDue:access' . date('Ymd') . ':' . date('H'),date('Y-m-d H:i:s'), json_encode(['mca' => $mcas,'nodes' => $nodes,'role' => $roleId]));
        RoleAccess::destroy(['role_id'=>$roleId, 'node_id'=>['in',$deletes]]);
    }

    public function getNodeSetting($roleid, $module, $controller, $action)
    {

    }

    public function getNodeAccess($roleid, $nodeid)
    {
        $roleAccess = new RoleAccess();
        if (!McaNode::get($nodeid)) {
            throw new JsonErrorException("非法mca");
        }
        $roleAccess->where('role_id', $roleid);
        $roleAccess->where('node_id', $nodeid);
        $roleAccess = $roleAccess->find();
        if ($roleAccess) {
            return $roleAccess->filters ?: [];
        } else {
            return [];
        }
    }

    public function setNodeAccess($roleid, $nodeid, $filter)
    {
        $roleAccess = new RoleAccess();
        if (!McaNode::get($nodeid)) {
            throw new JsonErrorException("非法mca");
        }
        $roleAccess->where('role_id', $roleid);
        $roleAccess->where('node_id', $nodeid);
        $roleAccess = $roleAccess->find();
        if ($roleAccess) {
            $roleAccess->filters = $filter;
        } else {
            $roleAccess = new RoleAccess();
            $roleAccess->role_id = $roleid;
            $roleAccess->node_id = $nodeid;
            $roleAccess->filters = $filter;
        }
        $roleAccess->save();
        $this->roleFillter->setFilters($roleid, $nodeid, json_decode($filter, true));
        return true;
    }

    public function getFilters($roleid)
    {
        $roleAccess = new RoleAccess();
        return $roleAccess->getFilters($roleid);
    }

    public function getAccessFilters($roleids, $nodeid)
    {
        if (count($roleids) <= 0) {
            return [];
        }
        $roleAccess = new RoleAccess();
        $roleAccess->where('node_id', $nodeid);
        $roleAccess->where('role_id', 'in', $roleids);
        $filters = $roleAccess->select();
        $result = [];
        foreach ($filters as $filter) {
            $result[] = $filter->filters;
        }
        return $result;
    }

    public function getMcas($roleid)
    {
        $nodes = RoleAccess::all(function (Query $query) use ($roleid) {
            $query->where('role_id', $roleid);
            $query->field('node_id');
        });
        return array_map(function ($node) {
            return $node->node_id;
        }, $nodes);
    }

    public function getNodes($roleid)
    {
        return RoleAccess::with('mca')->where('role_id', $roleid)->select();
    }

    public function getRbac($roleId)
    {
        if(empty($roleId)){   //修复下面死循环问题
            return NULL;
        }
        if ($rbac = RBAC::get($roleId)) {
            return $rbac;
        }
        $this->updateRoleAccess($roleId);
        return $this->getRbac($roleId);
    }

    public function setMcas($roleid, $mca)
    {
        $oldMca = $this->getMcas($roleid);
//        $diffFunc = function ($node1, $node2) {
//            if ((int)$node1 === (int)$node2) {
//                return 0;
//            } else {
//                return 1;
//            }
//        };
//        $deletes = array_udiff_assoc($oldMcas, $mcas, $diffFunc);
//        $increas = array_udiff_assoc($mcas, $oldMcas, $diffFunc);

        //删除差集
        $deleteData = array_diff($oldMca, $mca);
        //新增差集
        $addData = array_diff($mca, $oldMca);
        if (count($deleteData) > 0) {
            RoleAccess::where('role_id', $roleid)->where('node_id', 'in', $deleteData)->delete();
        }
        if (count($addData) > 0) {
            foreach ($addData as $increa) {
                $roleAccess = new RoleAccess();
                $roleAccess->role_id = $roleid;
                $roleAccess->node_id = $increa;
                $roleAccess->filters = [];
                $roleAccess->pages = [];
                $roleAccess->save();
            }
        }
        $this->refreshAccess($roleid);
        return true;
    }

    public function refreshAccess($roleid)
    {
        $this->removeOverdueAccess($roleid);
        if ($rbac = RBAC::get($roleid)) {
            $rbac->delete();
        }
        $this->updateErbcPages($roleid);
        $this->updateRoleAccess($roleid);
        $this->cache->delPermission($roleid);
        $menuServer = new Menu();
        $menuServer->clearUsersPages();
    }

    public function getNode($nodeId)
    {
        return McaNode::get($nodeId);
    }

    public function getPages($roleId)
    {
        if ($pages = $this->cache->getPages($roleId)) {
            return $pages;
        }
        $this->updateErbcPages($roleId);
        return $this->getPages($roleId);
    }

    public function updateErbcPages($roleId)
    {
        $mcas = $this->getMcas($roleId);
        $pages = [];
        foreach ($mcas as $nodeid) {
            if ($node = $this->getNode($nodeid)) {
                $pages[] = $node->route;
            }
        }
        $this->cache->setPages($roleId, array_unique($pages));
    }

    /**
     * 是否管理员
     * @param $userId
     * @return bool
     */
    public function isAdmin($userId)
    {
        $roles = RoleUser::getRoles($userId);
        $roleIds = array_map(function($role){
            return $role->role_id;
        },$roles);
        $role_ids = $roleIds;
        if(in_array(1, $role_ids)){
            return true;
        }
        return false;
    }

    /**
     * 获取角色名称
     * @param $user_id
     * @return string
     */
    public function getRoleNameByUserId($user_id,$allRole = [])
    {
        $role = '';
        if(!$allRole){
            $allRole = Cache::store('Role')->getRole();
        }
        $roles = RoleUser::getRoles($user_id);
        foreach ($roles as $v){
            $role .= ($allRole[$v['role_id']]['name'] ?? '') . ',';
        }
        $role = trim($role, ',');
        return $role;
    }

    /**
     * 复制角色
     * @param $role_id
     * @param $name
     * @return \think\response\Json
     */
    public function copy($role_id,$name)
    {
        $roleInfo = (new RoleModel())->field('id',true)->where(['id' => $role_id])->find();
        if(!empty($roleInfo)){
            $roleInfo = $roleInfo->toArray();
        }
        $roleInfo['update_time'] = time();
        $roleInfo['create_time'] = time();
        $roleInfo['name'] = $name;
        $roleAccessList = (new RoleAccess())->field('id',true)->where(['role_id' => $role_id])->select();
        $roleUserList = (new RoleUser())->field('id',true)->where(['role_id' => $role_id])->select();
        $roleModel = new RoleModel();
        //启动事务
        Db::startTrans();
        try {
            $roleModel->allowField(true)->isUpdate(false)->save($roleInfo);
            $new_role_id = $roleModel->id;
            $roleAccessData = [];
            foreach($roleAccessList as $k => $access){
                $access = $access->toArray();
                $access['role_id'] = $new_role_id;
                array_push($roleAccessData,$access);
            }
            (new RoleAccess())->allowField(true)->isUpdate(false)->saveAll($roleAccessData);
            $roleUserData = [];
            foreach($roleUserList as $k => $user){
                $user = $user->toArray();
                $user['role_id'] = $new_role_id;
                array_push($roleUserData,$user);
            }
            (new RoleUser())->allowField(true)->isUpdate(false)->saveAll($roleUserData);
            Cache::store('role')->delete();
            Db::commit();
            $roleInfo['id'] = $new_role_id;
            return $roleInfo;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage().$e->getFile().$e->getLine());
        }
    }
}