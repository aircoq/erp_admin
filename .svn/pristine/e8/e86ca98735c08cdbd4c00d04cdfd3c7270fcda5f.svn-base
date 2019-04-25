<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap;
use app\common\service\MonthlyModeConst;
use think\Request;
use app\report\service\MonthlyTargetDepartmentUserMapService as Server;

/**
 * @module 报表系统
 * @title 目标成员管理[销售]
 * @url /monthly-target-user
 */
class MonthlyTargetUser extends Base
{
    protected $server;

    protected function init()
    {
        if (is_null($this->server)) {
            $this->server = new Server();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $department_id = $params['department_id'] ?? 0;
        $user_name = $params['user_name'] ?? '';
        $mode = MonthlyModeConst::mode_sales;
        $result = $this->server->getUsers($department_id,$user_name,$mode);
        return json($result);
    }

    /**
     * @title 保存成员
     * @url add
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        $params = $request->param();
        $department_id = $params['department_id'];

        if(!$department_id){
            return json(['message' => '部门ID不能为空'], 400);
        }

        $user_ids = json_decode($params['user_ids'], true);
        if(!$user_ids){
            return json(['message' => '用户ID错误'], 400);
        }

        $mode = MonthlyModeConst::mode_sales;
        $result = $this->server->add($user_ids,$department_id,$mode);
        return json($result);
    }


    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id ： 用户ID
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = $request->param();
        $departmentModel = new MonthlyTargetDepartmentUserMap();
        $where['user_id'] = $id;
        $where['mode'] = MonthlyModeConst::mode_sales;
        $info = $departmentModel->where($where)->find();
        if (empty($info)) {
            return json(['message' => '该记录不存在']);
        }
        $result = $this->server->update($params,$where,$info);
        return json($result);
    }

    /**
     * @title 删除绑定关系
     * @url :id
     * @method delete
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $mode = MonthlyModeConst::mode_sales;
        $dataInfo = $this->server->deleteUser($id,$mode);
        return json(['message' => '删除成功', 'data' => $dataInfo], 200);
    }

    public function read($id)
    {
        if (empty($id)) {
            throw new JsonErrorException("请求参数错误");
        }

        $department = $this->server->info($id);
        return json($department, 200);
    }

    public function edit($id)
    {
        if (empty($id)) {
            throw new JsonErrorException("请求参数错误");
        }

        $department = $this->server->info($id);
        return json($department, 200);
    }


    /**
     * @title 拉取部门以及上级信息
     * @url get-department
     * @param Request $request
     * @return \think\response\Json
     */
    public function getDepartment(Request $request)
    {
        $params = $request->param();
        $department_id = $params['department_id'];

        if(!$department_id){
            return json(['message' => '部门ID不能为空'], 400);
        }
        $result = $this->server->getPidDepartment($department_id);
        return json($result);
    }


}