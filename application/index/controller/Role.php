<?php
namespace app\index\controller;

use app\common\exception\JsonErrorException;
use app\common\exception\RelateDeleteException;
use app\common\model\McaNode;
use app\common\model\RoleAccess;
use app\index\service\RoleLog;
use app\system\server\Menu;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\model\Role as RoleModel;
use app\common\model\RoleUser as RoleUserModel;
use app\index\service\Role as RoleServer;
use app\common\cache\driver\User;
use think\Exception;

/**
 * @module 用户系统
 * @title 角色管理
 * @author WCG
 * @package app\goods\controller
 * @url /role
 */
class Role extends Base
{
    public function index(Request $request)
    {
        $params = $request->param();
        $where = [];
        if (isset($params['status']) && is_numeric($params['status'])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['snText'])) {
            $where['name'] = ['like', '%'. $params['snText'] .'%'];
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        //总数
        $roleModel = new RoleModel();
        $count = $roleModel->where($where)->count();
        $roleModel = new RoleModel();
        $roleList = $roleModel->field('id, name, remark, status, sort')->where($where)->page($page,$pageSize)->select();
        Cache::handler()->set('sql',$roleModel->getLastSql());
        $result = [
            'data' => $roleList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * @title 新增
     */
    public function save(Request $request)
    {
        $params = $request->param();
        if (empty($params)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $roleModel = new RoleModel();
        $validateRole = validate('Role');
        if (!$validateRole->check($params)) {
            return json(['message' => $validateRole->getError()], 400);
        }
        //启动事务
        Db::startTrans();
        try {
            $params['create_time'] = time();
            $params['update_time'] = time();
            $roleModel->allowField(true)->isUpdate(false)->save($params);
            $user = Common::getUserInfo();
            $roleLog = new RoleLog();
            $roleLog->add($params['name'])->save($roleModel->id, $user['user_id'], $user['realname']);
            Cache::store('role')->delete();

            Db::commit();
            return json(['message' => '新增成功'], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败'], 500);
        }
    }

    public function read($id, RoleServer $roleServer)
    {
        if (empty($id)) {
            throw new JsonErrorException("请求参数错误");
        }
        $role = $roleServer->getRole($id);
        if(!$role){
            throw new JsonErrorException("不存在该角色");
        }
        $result = [

        ];
        return json($role);
    }

    /**
     * @title 修改
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }

        $roleModel = new RoleModel();
        $old = $roleModel->where('id', $id)->find();
        if (!$old) {
            return json(['message' => '该记录不存在'], 400);
        }

        if ($roleModel->isHas($id, $params['name'])) {
            return json(['message' => '该角色名称已存在'], 400);
        }

        //启动事务
        Db::startTrans();
        try {
            $params['update_time'] = time();
            $roleModel->allowField(true)->save($params, ['id' => $id]);
            $roleLog = new RoleLog();
            $userInfo = Common::getUserInfo();
            $roleLog->mdf($old['name'], $old, $params)->save($id, $userInfo['user_id'], $userInfo['realname']);
            Cache::store('role')->delete();
            Db::commit();
            return json(['message' => '更新成功'], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '更新失败'], 500);
        }
    }

    /**
     * @title 删除
     * @url /role/:id
     * @method delete
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $roleModel = new RoleModel();
        $old = $roleModel->where('id', $id)->find();
        if (!$old) {
            return json(['message' => '该记录不存在'], 400);
        }

        try {
            RoleModel::destroy(['id'=>$id]);
            Cache::store('role')->delete();

            $roleLog = new RoleLog();
            $userInfo = Common::getUserInfo();
            $roleLog->del($old['name'])->save($id, $userInfo['user_id'], $userInfo['realname']);

            return json(['message' => '操作成功']);
        } catch (RelateDeleteException $exception){
            return json_error($exception->getMessage());
        } catch (Exception $e) {
            return json(['message' => '操作失败'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 停用，启用账号'
     * @url /role/changeStatus
     */
    public function changeStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $status = $request->get('status', 0);
        if (empty($id) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $roleModel = new RoleModel();

        $old = $roleModel->where('id', $id)->find();
        if (!$old) {
            return json(['message' => '该角色不存在'], 400);
        }

        $roleLog = new RoleLog();
        $userInfo = Common::getUserInfo();
        Db::startTrans();
        try {
            $data['status'] = $status;
            $roleModel->allowField(true)->save($data, ['id' => $id]);
            Cache::store('role')->delete();
            $roleLog->mdf($old['name'], $old, $data)->save($id, $userInfo['user_id'], $userInfo['realname']);
            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 授权
     * @url /role/authorization
     */
    public function authorization(Request $request)
    {
        $id = $request->get('id', 0);
        $ids = $request->get('ids', '');
        if (empty($id) || empty($ids)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $idArr = explode(',', $ids);
        if (!is_array($idArr)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $data = [];
        foreach ($idArr as $k=>$v) {
            $data[$k]['role_id'] = $id;
            $data[$k]['node_id'] = $v;
        }
        Db::name('access')->where('role_id', $id)->delete();
        $res = Db::name('access')->insertAll($data);
        if ($res) {
            return json(['message' => '操作成功'], 200);
        }
        return json(['message' => '操作失败'], 500);
    }
    
    /**
     * @title 添加成员
     * @url /role/addUser
     * @method get
     */
    public function addUser(Request $request)
    {
        $id  = $request->get('id', 0);
        $ids = $request->get('ids', 0);
        if (empty($id)) {
            throw new JsonErrorException("请求参数错误");
        }
        // 当ids为零为空时，会导致插入到role_user表中的user_id为零的情况。因此需要过滤该参数 add by 2019-04-11
        $idArr = array_filter(explode(',', $ids));
        if (!is_array($idArr)) {
            throw new JsonErrorException("请求参数错误");
        }

        $menuServer = new Menu();
        $cacheUser  = new User();
        $roleUserModel = new RoleUserModel();
        $oldIds = $roleUserModel->getUserIds($id);
        $tempOldUser = [];
        $tempNewUser = [];
        $data = [];
        foreach ($idArr as $k=>$v) {
            $data[$k]['role_id'] = $id;
            $data[$k]['user_id'] = $v;
            $menuServer->cachePagesDel($v);
            if (in_array($v,$oldIds)) {
                $oldIds = array_diff($oldIds,[$v]);
            } else {
                array_push($tempNewUser,$cacheUser->getOneUserRealname($v));
            }
        }

        foreach ($oldIds as $key=>$val) {
            array_push($tempOldUser,$cacheUser->getOneUserRealname($val));
        }

        try{
            $roleUserModel->where('role_id', $id)->delete();
            if (!empty($data)) {
                $roleUserModel->saveAll($data, false);
                $userInfo = Common::getUserInfo();
                if (count($tempOldUser)>0 || count($tempNewUser)>0) {
                    $mdfStr = '';
                    if (count($tempOldUser)>0) {
                        $mdfStr .= '【删除成员】'.implode(',',$tempOldUser);
                    }

                    if (count($tempNewUser)>0) {
                        $mdfStr .= '>>【添加成员】'.implode(',',$tempNewUser);
                    }
                    $roleLog = new roleLog();
                    $roleLog->addMember($mdfStr)->save($id, $userInfo['user_id'], $userInfo['realname']);
                }
            }
            return json(['message' => '操作成功'], 200);
        }catch (Exception $e){
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @disabled
     */
    public function getNodeSetting(RoleServer $server, $roleid, $nodeid)
    {
        return json([]);
    }

    /**
     * @title 获取角色节点权限
     * @url /role/:roleid/node/:nodeid/access
     **/
    public function getNodeAccess(RoleServer $server, $roleid, $nodeid)
    {
        $access = $server->getNodeAccess($roleid, $nodeid);
        return json($access);
    }

    /**
     * @title 保存角色节点权限
     * @url /role/:roleid/node/:nodeid/access
     * @method POST
     * @param RoleServer $server
     * @param $roleid
     * @param $module
     * @param $controller
     * @param $action
     *
     **/
    public function setNodeAccess(RoleServer $server, $roleid, $nodeid)
    {
        $filter = Request::instance()->post('filter');
        $server->setNodeAccess($roleid, $nodeid, $filter);
        return json(['message' => '保存成功']);
    }

    /**
     * @title 获取角色已配路由
     * @url /role/:roleid/mcas
     */
    public function getMcas(RoleServer $server, $roleid)
    {
        $mcas = $server->getMcas($roleid);
        return json($mcas);
    }
    /**
     * @title 设置角色已配路由
     * @method post
     * @url /role/:roleid/mcas
     */
    public function setMcas(Request $request, RoleServer $server, $roleid)
    {
        $mcas = $request->post('mcas');
        $idArr = json_decode($mcas, true);
        if (!is_array($idArr)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $role_access = new RoleAccess();
        $oldAccess = $role_access->field('node_id')->where('role_id', $roleid)->select();
        $result = $server->setMcas($roleid, json_decode($mcas, true));
        if ($result) {
            $nodeIds = array_map(function($roleAccess){return $roleAccess['node_id'];},$oldAccess);
            $newIds = [];

            foreach ($idArr as $k=>$v) {
                if (in_array($v,$nodeIds)) {
                    $nodeIds = array_diff($nodeIds,[$v]);
                } else {
                    array_push($newIds,$v);
                }
            }

            if (count($nodeIds)>0 || count($newIds)>0) {
                $mca_node = new McaNode();
                $tempOld = '取消权限-》';
                $tempNew = '新增权限-》';
                $mdfStr = '';
                if (count($nodeIds)>0) {
                    $nodeArr = $mca_node->field('title,class_title,module')->where(['id'=>['in',implode(',',$nodeIds)]])->select();
                    if ($nodeArr) {
                        foreach ($nodeArr as $knode=>$vnode) {
                            $tempOld .= '【'.$vnode['module'].'>'.$vnode['class_title'].'>'.$vnode['title'].'】';
                        }
                        $mdfStr .= $tempOld;
                    }
                }

                if (count($newIds)>0) {
                    $nodeArr = $mca_node->field('title,class_title,module')->where(['id'=>['in',implode(',',$newIds)]])->select();
                    if ($nodeArr) {
                        foreach ($nodeArr as $kn=>$vn) {
                            $tempNew .= '【'.$vn['module'].'>'.$vn['class_title'].'>'.$vn['title'].'】';
                        }
                        $mdfStr .= '|'.$tempNew;
                    }
                }
                $userInfo = Common::getUserInfo();
                $roleLog = new RoleLog();
                $roleLog->mdfRole($mdfStr)->save($roleid, $userInfo['user_id'], $userInfo['realname']);
            }



            return json(['message' => '设置成功'], 200);
        }
        return json(['message' => '设置失败'], 500);
    }

    /**
     * @title 复制角色
     * @method post
     * @url :role_id/copy
     * @param $role_id
     * @return \think\response\Json
     */
    public function copy($role_id)
    {
        $request = Request::instance();
        $name = $request->post('name','');
        if(empty($name) || empty($role_id)){
            return json(['message' => '角色名称不能为空']);
        }
        $result = (new \app\index\service\Role())->copy($role_id,$name);
        return json(['message' => '复制成功','data' => $result]);
    }

    /**
     * @title 操作日志
     * @method get
     * @url :role_id/log
     * @param $role_id
     * @return \think\response\Json
     */
    public function log($role_id)
    {
        if(empty($role_id)){
            return json(['message' => '角色ID不能为空']);
        }

        $logService = new RoleLog();
        $result = $logService->getLog($role_id);
        foreach ($result as $key=>$value) {
            $result[$key]['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
        }

        return json($result, 200);
    }
}
