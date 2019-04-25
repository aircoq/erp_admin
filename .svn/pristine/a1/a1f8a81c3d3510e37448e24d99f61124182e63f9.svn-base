<?php

namespace app\report\controller;

use app\common\exception\JsonErrorException;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap;
use app\common\model\monthly\MonthlyTargetLog;
use app\common\service\MonthlyModeConst;
use app\common\service\UniqueQueuer;
use app\report\service\MonthlyDepartmentTypeService;
use app\report\service\MonthlyTargetDepartmentService;
use app\report\service\MonthlyTargetDepartmentUserMapService as MonthlyDepartmentUserMapService;
use app\index\service\JobService;
use think\exception\ErrorException;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\monthly\MonthlyTargetDepartment as MonthlyDepartmentModel;
use app\common\model\User as UserModel;
use app\report\service\MonthlyTargetDepartmentService as Server;

/**
 * @module 报表系统
 * @title 目标部门管理[开发]
 * @url /develop-monthly-target-department
 */
class DevelopMonthlyTargetDepartment extends Base
{
    public function index(Request $request)
    {
        $type = $request->get('type', 0);
        $superior = $request->get('is_superior', 0);
        $params = $request->param();
        $mode = MonthlyModeConst::mode_development;
        $where = [];
        $where[] = ['mode', '==',$mode];
        if (isset($params['status']) && is_numeric($params['status'])) {
            $where[] = ['status', '==', $params['status']];
        }
        if (isset($params['snText']) && !empty($params['snText'])) {
            $where[] = ['name', 'like', $params['snText']];
        }
        $is_superior = false;
        if (!empty($type)) {
            if (!empty($superior)) {
                $departmentTypeService = new MonthlyDepartmentTypeService();
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
        $departmentList = Cache::store('MonthlyDepartment')->tree();
        if (isset($where) && !empty($where)) {
            $departmentList = Cache::filter($departmentList, $where);
        }
        foreach ($departmentList as $key => &$department) {
            $department['target_amount'] = $department['target_amount'] ?? 0;
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
        $departmentModel = new MonthlyDepartmentModel();
        $validateDepart = validate('MonthlyDepartment');
        $params['mode'] = MonthlyModeConst::mode_development;
        if (!$validateDepart->check($params)) {
            return json(['message' => $validateDepart->getError()], 400);
        }
        $leader = [];
        if (isset($params['leader_id'])) {
            $leader = json_decode($params['leader_id'], true);
        }
        $departmentService = new Server();
        //启动事务
        Db::startTrans();
        try {
            $params['create_time'] = time();
            $params['update_time'] = time();
            $departmentModel->allowField(true)->isUpdate(false)->save($params);
            $department_id = $departmentModel->id;
            MonthlyTargetLog::AddLog(MonthlyTargetLog::department, $department_id, 0, '添加部门：' . json_encode($params));
            Cache::store('MonthlyDepartment')->deleteAll();

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
     * 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id ： 部门ID
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = $request->param();
        $mode = MonthlyModeConst::mode_development;
        $departmentModel = new MonthlyDepartmentModel();
        $info = $departmentModel->field('pid')->where(['id' => $id])->find();
        if (empty($info)) {
            return json(['message' => '该记录不存在']);
        }
        if ($departmentModel->isHas($id, $params['name'], $info['pid'],$mode)) {
            return json(['message' => '该部门名称已存在'], 400);
        }

        $departmentService = new Server();
        $allInfo = $departmentService->getAllDepartmentTree();
        $cacheInfo = $allInfo[$id] ?? '';
        if ($params['status'] == 1 && $cacheInfo && $params['status'] != $cacheInfo['status']) {
            if ($cacheInfo['is_bottom'] == 1) {
                //查询成员是否还有开启的
                $mapWhere['department_id'] = $cacheInfo['id'];
                $mapWhere['status'] = 0;
                $has = (new MonthlyTargetDepartmentUserMap())->where($mapWhere)->value('id');
                if ($has) {
                    return json(['message' => '该组门下的成员还有启用状态，不能设置 停用'], 400);
                }

            } else {
                foreach ($cacheInfo['child_ids'] as $childId) {
                    $child = $allInfo[$childId] ?? '';
                    if ($child && $child['status'] == 0) {
                        return json(['message' => '该部门下的小组还有启用状态，不能设置 停用'], 400);
                    }
                }
            }
        }


        //启动事务
        Db::startTrans();
        try {
            $pid = $params['pid'];
            $dep = $departmentModel->field('id')->where('id', $pid)->where('pid', $id)->find();
            if ($dep) {
                return json(['message' => '部门上下级关系错误'], 400);
            }
            $params['update_time'] = time();
            MonthlyTargetLog::AddLog(MonthlyTargetLog::department, $id, 0, '修改部门：' . json_encode($params));
            $departmentModel->allowField(true)->save($params, ['id' => $id]);

            Cache::store('MonthlyDepartment')->deleteAll();
            Db::commit();
            $info = $departmentService->info($id);
            return json(['message' => '更新成功', 'data' => $info], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /** 删除
     * @param $id
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $modeServer = new MonthlyTargetDepartmentService();
        Db::startTrans();
        try {
            MonthlyDepartmentModel::destroy($id);
            MonthlyTargetLog::AddLog(MonthlyTargetLog::department, $id, 0, '删除部门：' . $modeServer->getDepartmentNames($id));
            Cache::store('MonthlyDepartment')->deleteAll();
            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (ErrorException $e) {
            Db::rollback();
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 停用，启用账号
     * @url change-status
     */
    public function changeStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $status = $request->get('status', 0);
        if (empty($id) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $departmentModel = new MonthlyDepartmentModel();
        $statusMsg = ['启用', '停用'];
        Db::startTrans();
        try {
            $data['status'] = $status;
            $departmentModel->allowField(true)->save($data, ['id' => $id]);
            MonthlyTargetLog::AddLog(MonthlyTargetLog::department, $id, 0, '状态改为：' . $statusMsg[$status]);
            Cache::store('MonthlyDepartment')->deleteAll();
            Db::commit();
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 获取所有部门
     * @url get-department
     */
    public function getDepartment(Request $request,Server $server)
    {

        $mode = MonthlyModeConst::mode_development;
        $result = $server->getAllDepartmentTree(0,$mode,true);

        return json($result, 200);
    }

    /**
     * @title 部门类型
     * @url type
     * @method get
     * @return \think\response\Json
     */
    public function type()
    {
        $typeList = (new MonthlyDepartmentTypeService())->info();
        $typeInfo = [];
        foreach ($typeList as $k => $v) {
            $temp = [];
            $temp['id'] = $v['id'];
            $temp['name'] = $v['name'];
            array_push($typeInfo, $temp);
        }
        return json($typeInfo);
    }
}
