<?php

namespace app\index\controller;

use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\index\service\Department;
use app\index\service\DepartmentUserMapService;
use erp\ErpRbac;
use think\db\Query;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\User as UserModel;
use app\common\model\RoleUser;
use think\Exception;
use app\index\service\User as UserService;
use app\index\service\UserLog;

/**
 * @module 用户系统
 * @title 用户管理
 * @author phill
 * @url /user
 * @package app\goods\controller
 */
class User extends Base
{
    /**
     * @title 显示资源列表
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $server = new UserService();
        $result = $server->getUserList($params);
        return json($result, 200);
    }

    /**
     * @title 添加用户
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        if (empty($params) || !isset($params['role_id'])) {
            return json(['message' => '请求参数错误'], 400);
        }
        if (UserModel::onlyTrashed()->where('username', $params['username'])->find()) {
            throw new JsonErrorException("用户已被使用");
        }
        if (UserModel::onlyTrashed()->where('email', $params['email'])->find()) {
            throw new JsonErrorException("邮箱已被使用");
        }
        if (UserModel::onlyTrashed()->where('mobile', $params['mobile'])->find()) {
            throw new JsonErrorException("手机号已被使用");
        }
        if (!param($params, 'role_id')) {
            throw new JsonErrorException("必需给新用户至少指定一个角色");
        }
        if (!isset($params['department_id'])) {
            return json(['message' => '用户所属部门为必填'], 400);
        }

        // 添加用户时，职务不能为空 add by 2019-04-20
        $depts = json_decode($params['department_id'], true);
        $jobNum = array_filter(array_column($depts, 'job_id'));
        if(empty($jobNum)){
            throw new Exception("用户所属部门职务必填");
        }

        $validateUser = validate('User');
        if (!$validateUser->check($params)) {
            return json(['message' => $validateUser->getError()], 400);
        }
        $params['register_ip'] = $request->ip();
        $server = new UserService();
        $server->add($params);
        return json(['message' => '新增成功']);
    }

    /**
     * @title 查看用户
     * @param  int $id : 用户ID
     * @return \think\Response
     */
    public function read($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $userService = new UserService();
        $list = $userService->info($id);
        return json($list, 200);
    }

    /**
     * @title 查看用户
     * @param  int $id : 用户ID
     * @return \think\Response
     */
    public function edit($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $userService = new UserService();
        $list = $userService->info($id);
        return json($list, 200);
    }

    /**
     * @title 更新用户
     * @param  \think\Request $request
     * @param  int $id : 用户ID
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = $request->param();
        $user = UserModel::get($id);
        try {
            if (!$user) {
                throw new Exception("非法处理");
            }
            if (param($params, 'username') !== $user->username) {
                if (UserModel::where('username', $params['username'])->find()) {
                    throw new Exception("用户名已被其它用户使用");
                }
            }
            if (param($params, 'email') !== $user->email) {
                if (UserModel::where('email', $params['email'])->find()) {
                    throw new Exception("邮箱已被其它用户使用");
                }
            }
            if (param($params, 'mobile') !== $user->mobile) {
                if (UserModel::where('mobile', $params['mobile'])->find()) {
                    throw new Exception("手机号已被其它用户使用");
                }
            }

            // 编辑用户时，部门与职务不能为空 add by 2019-04-20
            if (!isset($params['department_id'])){
                throw new Exception("用户所属部门为必填");
            }

            $depts = json_decode($params['department_id'], true);
            $jobNum = array_filter(array_column($depts, 'job_id'));
            if(empty($jobNum)){
                throw new Exception("用户所属部门职务必填");
            }

            $server = new UserService();
            $server->update($id, $params);
            return json(['message' => '修改成功']);
        } catch (Exception $ex) {
            $err = ['message' => $ex->getMessage(), 'file' => $ex->getFile(), 'line' => $ex->getLine()];
            return json($err, 400);
        }
    }

    /**
     * @title 删除用户
     * @param  int $id : 用户ID
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        try {
            RoleUser::where('user_id', $id)->delete();
            UserModel::destroy($id);
            Cache::store('user')->delete($id);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 获取所有部门和角色
     * @url departmentAndRole
     */
    public function departmentAndRole()
    {
        $department = Cache::store('department')->getDepartment();
        $departmentList = [];
        if ($department) {
            foreach ($department as $k => $v) {
                $departmentList[$k]['id'] = $v['id'];
                $departmentList[$k]['name'] = $v['name'];
            }
            $departmentList = array_values($departmentList);
        }
        $role = Cache::store('role')->getRole();
        $roleList = [];
        if ($role) {
            foreach ($role as $k => $v) {
                $roleList[$k]['id'] = $v['id'];
                $roleList[$k]['name'] = $v['name'];
                $roleList[$k]['remark'] = $v['remark']; // 返回角色描述 add by 2019-4-18
            }
            $roleList = array_values($roleList);
        }
        $data = [
            'departmentList' => $departmentList,
            'roleList' => $roleList,
        ];
        return json($data, 200);
    }

    /**
     * @title 停用，启用账号
     * @url status
     * @param Request $request
     * @return \think\response\Json
     */
    public function changeStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $status = $request->get('status', 0);
        if (empty($id) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $userModel = new UserModel();
        $old = $userModel->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该用户不存在');
        }
        $userInfo = Common::getUserInfo();
        Db::startTrans();
        try {
            $userModel = new UserModel();
            $data['status'] = $status;
            $userModel->allowField(true)->save($data, ['id' => $id]);
            Cache::store('user')->updateUserInfo($id);
            $UserLog = new UserLog();
            $UserLog->mdf($old['realname'], $old, $data)
                ->save($id, $userInfo['user_id'], $userInfo['realname']);
            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 批量禁用
     * @url batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request)
    {
        $params = $request->param();
        $ids = $params['ids'];
        $status = $params['status'];
        if (empty($ids) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $data['status'] = $status;
        $where['id'] = ['in', $ids];
        try {
            $userModel = new UserModel();
            $result = $userModel->save($data, $where);
            if ($result) {
                $idsArr = explode(',', $ids);
                $userCache = Cache::store('user');
                foreach ($idsArr as $v) {
                    $userCache->delete($v);
                }
            }
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 修改密码
     * @url updatePassword
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function updatePassword(Request $request)
    {
        $params = $request->param();
        $old_password = $params['old_password'];
        $password = $params['password'];
        $con_password = $params['confirm_password'];
        if (empty($old_password) || empty($password) || empty($con_password)) {
            return json(['message' => '请求参数错误'], 400);
        }
        if ($password != $con_password) {
            return json(['message' => '两次输入的密码不一致'], 400);
        }

        //1、长度为8-16个字符；
        $pwdlen = strlen($password);
        if ($pwdlen < 8 || $pwdlen > 16) {
            return json(['message' => '新密码长度必须为8-16个字符'], 400);
        }
        //2、不能使用中文、空格；
        if (preg_match("/^[\x7f-\xff]| $/", $password)) {
            return json(['message' => '新密码不能包含中文'], 400);
        }
        if (preg_match("/ /", $password)) {
            return json(['message' => '新密码不能包含空格'], 400);
        }
        //3、至少含数字/字母/符号2种组合；
        $pwdlen = 0;
        if (preg_match('/[A-Za-z]/', $password)) {
            $pwdlen++;
        }
        if (preg_match('/[0-9]/', $password)) {
            $pwdlen++;
        }
        if (!preg_match("/^[0-9a-zA-Z]*$/", $password)) {
            $pwdlen++;
        }
        if ($pwdlen < 2) {
            return json(['message' => '新密码至少含数字/字母/符号2种组合'], 400);
        }

        //以字母开头，长度在8~18之间，只能包含字符、数字和下划线
        $ok = preg_match_all("/^[a-zA-Z\d_]{7,17}$/", $password, $array);
        if (empty($ok)) {
            return json(['message' => '密码以字母开头，长度在8~18之间，只能包含字符、数字和下划线'], 400);
        }
        $userModel = new UserModel();
        $userCache = Cache::store('user');
        $userInfo = Common::getUserInfo();
        $user = $userCache->getOneUser($userInfo['user_id']);
        if (!$user) {
            return json(['message' => '该用户不存在或已被删除'], 400);
        }
        if ($user['password'] != $userModel->getHashPassword($old_password, $user['salt'])) {
            return json(['message' => '你输入的原始密码不对'], 400);
        }
        $salt = $userModel->getSalt();
        $data['password'] = $userModel->getHashPassword($password, $salt);
        $data['salt'] = $salt;
        $data['is_first'] = 1;
        Db::startTrans();
        try {
            $userModel->where('id', $userInfo['user_id'])->setField($data);
            $userCache->updateUserInfo($userInfo['user_id']);
            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
        }
        return json(['message' => '操作失败'], 500);
    }

    /**
     * @title 重置密码
     * @url :id/reset-password
     * @method post
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function resetPassword($id)
    {
        $userModel = new UserModel();
        $userCache = Cache::store('user');
        $userInfo['user_id'] = $id;
        $user = $userCache->getOneUser($userInfo['user_id']);
        if (!$user) {
            return json(['message' => '该用户不存在或已被删除'], 400);
        }
        $salt = $userModel->getSalt();
        $data['password'] = $userModel->getHashPassword(UserModel::DefaultPassword, $salt);
        $data['salt'] = $salt;
        Db::startTrans();
        try {
            $userModel->where('id', $userInfo['user_id'])->setField($data);
            $userCache->updateUserInfo($userInfo['user_id']);
            Db::commit();
            return json(['message' => '重置成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
        }
        return json(['message' => '重置失败'], 500);
    }

    /**
     * @title 获取角色下的成员
     * @url member
     * @param Request $request
     * @return \think\response\Json
     */
    public function member(Request $request)
    {
        $id = $request->get('id', 0);
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $field = 'id, username, realname';
        $query = function (Query $query) use ($id) {
            $query->where('role_id', $id);
        };
        $userList = UserModel::hasWhere('roleUser', $query)->field($field)->select();
        if ($userList) {
            $departmentServer = new \app\index\service\Department();
            foreach ($userList as $key => $user) {
                //$depName = $departmentServer->getDepartmentNames($user['department_id']);
                //$user['department'] = $depName;
                unset($user['roleUser']);
            }
        }
        $result = [
            'data' => $userList,
            'role_id' => $id,
        ];
        return json($result, 200);
    }

    /**
     * @title 获取员工的信息
     * @url :type(\w+)/staffs
     * @method get
     * @param $type
     * @return \think\response\Json
     */
    public function staffs($type)
    {
        $request = Request::instance();
        $content = $request->get('content', '');
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 0);
        $userService = new UserService();
        $result = $userService->staff($type, $content, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 获取领导
     * @url :type(\w+)/:work(\w+)/leaders
     * @method get
     * @param $type
     * @return \think\response\Json
     */
    public function leaders($type, $work)
    {
        $request = Request::instance();
        $content = $request->get('content', '');
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 0);
        $userService = new UserService();
        $result = $userService->staff($type . "-" . $work, $content, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @noauth
     * @title 获取用户filter过滤器列表
     * @url getFilters
     */
    public function getFilters(Request $request)
    {
        $nodeId = $request->get('nodeid');
        $userId = $request->get('userid');
        $Rbac = ErpRbac::getRbac($userId);
        dump_detail($Rbac->getFilters($nodeId));
    }

    /**
     * @title 验证旧密码
     * @url check-password
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function checkPassword(Request $request)
    {
        $old_password = $request->post('old_password');
        $userModel = new UserModel();
        $userInfo = Common::getUserInfo();
        $userCache = Cache::store('user');
        $user = $userCache->getOneUser($userInfo['user_id']);
        if (!$user) {
            return json(['message' => '该用户不存在或已被删除'], 400);
        }
        if ($user['password'] != $userModel->getHashPassword($old_password, $user['salt'])) {
            return json(['message' => '你输入的原始密码不对'], 400);
        }
        return json(['message' => '正确']);
    }


    /**
     * @title 模拟登陆
     * @url simulation-on
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function simulationOn(Request $request)
    {
        $userId = $request->post('id');
        if (!$userId) {
            return json(['message' => '用户ID错误'], 400);
        }
        $userService = new UserService();
        $result = $userService->simulationOn($userId);
        return $result;
    }

    /**
     * @title 更新当前用户token
     * @url update-token
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function updateToken(Request $request)
    {
        $token = Common::getUpdateToken();
        if (!$token) {
            return json(['message' => '更新失败'], 400);
        }
        return json(['message' => '更新成功', 'token' => $token]);
    }

    /**
     * @title 获取用户的部门信息
     * @url :id/get-department
     * @method get
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function getUserDepartment($id)
    {

        $userService = new UserService();
        $result = $userService->getUserDepartment($id);
        return json($result);
    }

    /**
     * @title 获取用户日志
     * @method get
     * @url :id/logs
     * @author starzhan <397041849@qq.com>
     */
    public function getUserLog($id)
    {
        $userService = new UserService();
        $result = $userService->getLogList($id);
        return json($result, 200);
    }

    /**
     * @title 获取登录用户的信息
     * @method get
     * @url login-user-position
     * @author starzhan <397041849@qq.com>
     */
    public function getUserPositionByLoginUser()
    {
        $param = $this->request->param();
        if (isset($param['user_id']) && $param['user_id']) {
            $userId = $param['user_id'];
        }else{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
        }
        $userService = new UserService();
        $userInfo = $userService->getUserPositionByLoginUser($userId);
        return json($userInfo, 200);
    }

    /**
     * @title 仅仅获取用户的id和name
     * @url usernameList
     * @method get
     * @return \think\response\Json
     * @throws Exception
     */
    public function getUsernameList()
    {

        $params = $this->request->param();

        // 筛选条件
        $where['where'] = [];
        if (isset($params['status'])) {
            $where['where']['status'] = ['eq', (int)$params['status']];
        }
        if (isset($params['on_job'])) {
            $where['where']['on_job'] = ['eq', (int)$params['on_job']];
        }

        // 分页参数
        $where['page'] = (isset($params['page']) && $params['page'] > 0) ? (int)$params['page'] : 1;
        $where['pageSize'] = (isset($params['pageSize']) && $params['pageSize'] > 0) ? (int)$params['pageSize'] : 20;

        $result = (new UserService())->getUserFields($where);
        return json($result, 200);
    }
}
