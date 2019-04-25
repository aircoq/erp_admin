<?php

namespace app\index\controller;

use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\index\service\DepartmentLog;
use app\index\service\DepartmentTagMapService;
use app\index\service\DepartmentTypeService;
use app\index\service\DepartmentUserMapService;
use app\index\service\JobService;
use think\Exception;
use think\exception\ErrorException;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\Department as DepartmentModel;
use app\common\model\User as UserModel;
use app\common\model\DepartmentUserMap;
use app\index\service\Department as Server;

/**
 * @module 用户系统
 * @title 部门管理
 * @url /department
 */
class Department extends Base
{
    /**
     * @title 部门列表
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 0);
        $superior = $request->get('is_superior', 0);
        $params = $request->param();
        $where = [];
        if (isset($params['status']) && is_numeric($params['status'])) {
            $where[] = ['status', '==', $params['status']];
        }
        if (isset($params['snText']) && !empty($params['snText'])) {
            $where[] = ['name', 'like', $params['snText']];
        }
        $is_superior = false;
        if (!empty($type)) {
            if (!empty($superior)) {
                $departmentTypeService = new DepartmentTypeService();
                $info = $departmentTypeService->info($type);
                $superior = json_decode($info['superior'], true);
                if (!empty($superior)) {
                    $where[] = ['type', 'in', $superior];
                    $is_superior = true;
                } else {
                    return json([]);
                }
            } else {
                $where[] = ['type', '==', $superior];
            }
        }
        //总数
        $departmentList = Cache::store('department')->tree();
        if (isset($where) && !empty($where)) {
            $departmentList = Cache::filter($departmentList, $where);
        }

        $departmentTagMapServer = new DepartmentTagMapService();
        // 根据标签筛选部门 add by 2019-04-22
        if (!empty($params['tag_id'])) {
            $deptIds = array_column($departmentList, 'id');             // 所有的部门id
            $deptTagIds = $departmentTagMapServer->getDeptTagId($deptIds);     // 部门id + 标签id
            $existsDeptIds = array_unique(array_column($deptTagIds, 'department_id')); // 已绑定的部门
            foreach ($deptTagIds as $key=>$val){
                if (!(in_array($val['department_id'], $existsDeptIds)
                    && $val['department_tag_id'] == $params['tag_id']))
                {
                   unset($deptTagIds[$key]);
                }
            }
            $realDeptIds = array_column($deptTagIds, 'department_id'); // 过滤后的部门id
        }

        $codeServer = new JobService();
        $DepartmentModel = new DepartmentModel();

        foreach ($departmentList as $key => $department) {
            if (!isset($department['job'])) {  continue; }
            if(!empty($params['tag_id']) && !in_array($department['id'], $realDeptIds)){
                unset($departmentList[$key]);
                continue;
            }

            $department['job'] = $codeServer->getName($department['job']);

            // 部门列表，所属平台应该显示title。与编辑部门页面所属平台保存一致 add by 2019-04-18
            $department['channel_name'] = $DepartmentModel->getChannelTitle(['channel_id' => $department['channel_id']]);
            //$department['channel_name'] = $DepartmentModel->getChannelNameAttr(null, ['channel_id' => $department['channel_id']]);

            $departmentList[$key] = $department;

            // 添加返回部门标签 modify by 2019-04-12
            $departmentList[$key]['tag_name'] = $departmentTagMapServer->get($department['id']);
        }

        // 根据标签筛选部门，数据列表可能会空  add by 2019-04-22
        if (!empty($params['tag_id']) && count($departmentList) == 1) {
            unset($departmentList['child_ids']);
        }

        if ($is_superior && !empty($departmentList)) {
            $childData = [];
            foreach ($departmentList as $k => $v) {
                $childData = array_merge($childData, $v['child_ids']);
            }
            $child_ids = [];
            foreach ($departmentList as $k => $v) {
                if (!in_array($k, $childData)) {
                    array_push($child_ids, intval($k));
                }
            }
            $departmentList['child_ids'] = $child_ids;
        }
        return json($departmentList, 200);
    }

    public function save(Request $request)
    {
        $params = $request->param();
        if (empty($params)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $departmentModel = new DepartmentModel();
        $validateDepart = validate('Department');
        if (!$validateDepart->check($params)) {
            return json(['message' => $validateDepart->getError()], 400);
        }
        $leader = [];
        if (isset($params['leader_id'])) {
            $leader = json_decode($params['leader_id'], true);
        }
        $departmentService = new Server();
        $departmentUserMapService = new DepartmentUserMapService();
        //启动事务
        Db::startTrans();
        try {
            $params['create_time'] = time();
            $params['update_time'] = time();

            $user = Common::getUserInfo();

            $departmentModel->allowField(true)->isUpdate(false)->save($params);
            $department_id = $departmentModel->id;

            // 保存部门与标签 add by 2019-04-04
            if (!empty($params['tag_ids'])) {
                $tags = json_decode($params['tag_ids'], true);
                $tagIds = array_filter(array_unique($tags));
                if(!empty($tagIds)){
                    (new DepartmentTagMapService())->add($department_id, $tagIds);
                }
            }

            //自动关联 账号资料成员信息
            $temp = [
                'departmentId' => $department_id,
                'leader' => $leader,
                'oldLeader' => [],
                'user' => [
                    'realname' =>'[新增负责人]' . $user['realname'],
                    'user_id' => $user['user_id'],
                ]
            ];
            $service = new UniqueQueuer(\app\index\queue\DepartmentUserMapBatchQueue::class);
            $service->push($temp);
            //设置负责人
            $is_delete = true;
            foreach ($leader as $key => $value) {
                $departmentUserMapService->setLeader($value['user_id'], $department_id, $value['job_id'], $is_delete);
                $is_delete = false;
            }

            Cache::store('department')->delete();
            // 该清楚缓存的方法已放入上面delete（）方法中 mdf 2019-04-08
            //Cache::handler()->del('cache:department_tree');

            $DepartMentLog = new DepartmentLog();
            $DepartMentLog->add($params['name'])->save($department_id, $user['user_id'], $user['realname']);
            Db::commit();
            $info = $departmentService->info($department_id);
            return json(['message' => '新增成功', 'data' => $info], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败'], 500);
        }
    }

    public function read($id, Server $server)
    {
        if (empty($id)) {
            throw new JsonErrorException("请求参数错误");
        }
        if (!$department = $server->getDepartment($id)) {
            throw new JsonErrorException("部门不存在");
        }
        $department = $server->info($id);
        return json($department, 200);
    }

    public function edit($id, Server $server)
    {
        if (empty($id)) {
            throw new JsonErrorException("请求参数错误");
        }
        if (!$department = $server->getDepartment($id)) {
            throw new JsonErrorException("部门不存在");
        }
        $department = $server->info($id);
        return json($department, 200);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id ： 部门ID
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = $request->param();
        $userInfo = Common::getUserInfo();
        $departmentModel = new DepartmentModel();
        $info = $departmentModel->field('*')->where(['id' => $id])->find();
        if (empty($info)) {
            return json(['message' => '该记录不存在']);
        }
        if ($departmentModel->isHas($id, $params['name'], $info['pid'])) {
            return json(['message' => '该部门名称已存在'], 400);
        }
        $leader = [];
        if (isset($params['leader_id'])) {
            $departmentUserMapModel = new DepartmentUserMap();
            $leaderList = $departmentUserMapModel->field('user_id,job_id')->where([
                'department_id' => $id,
                'is_leader' => 1
            ])->select();
            $info['leader_id'] = $leaderList;
            $leader = json_decode($params['leader_id'], true);
        }
        $departmentService = new Server();
        $departmentUserMapService = new DepartmentUserMapService();

        $pid = $params['pid'];
        if ($pid == $id) {
            return json(['message' => '上级部门不能为自己'], 400);
        }

        $dep = $departmentModel->field('id')->where('id', $pid)->where('pid', $id)->find();
        if ($dep) {
            return json(['message' => '部门上下级关系错误'], 400);
        }

        //启动事务
        Db::startTrans();
        try {

            $params['update_time'] = time();
            $departmentModel->allowField(true)->save($params, ['id' => $id]);

            // 保存部门与标签 add by 2019-04-04
            if (!empty($params['tag_ids'])) {
                $tags = json_decode($params['tag_ids'], true);
                $tagIds = array_filter(array_unique($tags));
                (new DepartmentTagMapService())->add($id, $tagIds);
            }

            $is_delete = true;
            //自动关联 账号资料成员信息
            $temp = [
                'departmentId' => $id,
                'leader' => $leader,
                'oldLeader' => (new DepartmentUserMapService())->getLeader($id),
                'user' => [
                    'realname' => '[更换负责人]' . $userInfo['realname'],
                    'user_id' => $userInfo['user_id'],
                ]
            ];
            $service = new UniqueQueuer(\app\index\queue\DepartmentUserMapBatchQueue::class);
            $service->push($temp);
            //设置负责人
            foreach ($leader as $key => $value) {
                $departmentUserMapService->setLeader($value['user_id'], $id, $value['job_id'], $is_delete);
                $is_delete = false;
            }
            $DepartmentLog = new DepartmentLog();
            $params['leader_id'] = $leader;

            $DepartmentLog->mdf($info['name'], $info->toArray(), $params)
                ->save($id, $userInfo['user_id'], $userInfo['realname']);

            Cache::store('department')->delete();
            // 该清楚缓存的方法已放入上面delete（）方法中 mdf 2019-04-08
            //Cache::handler()->del('cache:department_tree');

            Db::commit();
            $info = $departmentService->info($id);
            return json(['message' => '更新成功', 'data' => $info], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @title 删除
     * @param $id
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }

        Db::startTrans();
        try {
            DepartmentModel::destroy($id);
            Cache::store('department')->delete();
            // 该清楚缓存的方法已放入上面delete（）方法中 mdf 2019-04-08
            //Cache::handler()->del('cache:department_tree');

            // 删除部门的同时删除部门对应的标签 add by 2019-04-13
            (new DepartmentTagMapService())->delByDeptId($id);

            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (ErrorException $e) {
            Db::rollback();
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 停用，启用账号
     * @url changeStatus
     */
    public function changeStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $status = $request->get('status', 0);
        if (empty($id) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $userInfo = Common::getUserInfo();
        $departmentModel = new DepartmentModel();
        $old = $departmentModel->where('id', $id)->find();
        if (!$old) {
            throw new Exception('当前部门不存在');
        }
        $departmentLog = new DepartmentLog();
        Db::startTrans();
        try {
            $data['status'] = $status;
            $departmentModel->allowField(true)->save($data, ['id' => $id]);
            $departmentLog->mdf($old['name'], $old, $data)
                ->save($id, $userInfo['user_id'], $userInfo['realname']);
            Cache::store('department')->delete();
            // 该清楚缓存的方法已放入上面delete（）方法中 mdf 2019-04-08
            //Cache::handler()->del('cache:department_tree');

            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 获取所有部门
     * @url getDepartment
     */
    public function getDepartment(Server $server)
    {
        $departments = $server->getDepartments();
        $result = [
            'department' => $departments,
        ];
        return json($result, 200);
    }

    /**
     * @title 获取公司信息
     * @url /company
     */
    public function company()
    {
        $service = new Server();
        $result = $service->company();
        return json($result, 200);
    }

    /**
     * @title 获取用户
     * @url getUser
     */
    public function getUser(Request $request)
    {
        $snText = $request->get('snText', '');
        $depId = $request->get('depId');
        $userModel = new UserModel();
        $departmentUserMapService = new DepartmentUserMapService();
        $where = [];
        if ($snText) {
            $where['username'] = ['like', '%' . $snText . '%'];
        }
        if ($depId) {
            $where['id'] = ['in', $departmentUserMapService->getUserByDepartmentId($depId)];
        }
        $userList = $userModel->field('id, realname as username')->where($where)->select();
        $data = [
            'userList' => $userList,
        ];
        return json($data, 200);
    }

    /**
     * @title 保存调序
     * @url sort
     * @method post
     * @return \think\response\Json
     */
    public function sort()
    {
        $request = Request::instance();
        $sort = $request->post('sort', '');
        if (empty($sort)) {
            return json(['message' => '无须排序']);
        }
        $sort = json_decode($sort, true);
        (new Server())->sort($sort);
        return json(['message' => '保存成功']);
    }

    /**
     * @title 部门类型
     * @url type
     * @method get
     * @return \think\response\Json
     */
    public function type()
    {
        $typeList = (new DepartmentTypeService())->info();
        $typeInfo = [];
        foreach ($typeList as $k => $v) {
            $temp = [];
            $temp['id'] = $v['id'];
            $temp['name'] = $v['name'];
            array_push($typeInfo, $temp);
        }
        return json($typeInfo);
    }

    /**
     * @title 获取部门修改日志
     * @method get
     * @url :id(\d+)/logs
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function log($id)
    {
        $departmentService = new Server();
        $result = $departmentService->getLogList($id);
        return json($result, 200);
    }

    /**
     * @title 获取对应渠道的销售
     * @method get
     * @url :id/department-users
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function departmentUserByChannelId($id)
    {
        $departmentService = new DepartmentUserMapService();
        $result = $departmentService->departmentUserByChannelId($id);
        return json($result, 200);
    }
}
