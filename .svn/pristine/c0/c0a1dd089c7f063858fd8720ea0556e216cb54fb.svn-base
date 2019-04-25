<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Account;
use app\common\model\AccountUserMap;
use app\common\model\monthly\MonthlyTargetAmount;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap as DepartmentUserMap;
use \app\common\model\monthly\MonthlyTargetDepartment as DepartmentModel;
use app\common\model\monthly\MonthlyTargetLog;
use app\common\model\User;
use app\common\service\MonthlyModeConst;
use app\index\service\DepartmentUserMapService;
use app\report\service\MonthlyTargetDepartmentService as DepartmentServer;

use think\Db;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimoin
 * Date: 18-10-31
 * Time: 上午10:28
 */
class MonthlyTargetDepartmentUserMapService
{
    protected $departmentUserMapModel = null;

    public function __construct()
    {
        if (is_null($this->departmentUserMapModel)) {
            $this->departmentUserMapModel = new DepartmentUserMap();
        }
    }

    /**
     * 获取人员与部门的关系
     * @param int $user_id
     * @param int $department_id
     * @param int $mode
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getMap($user_id = 0, $department_id = 0, $mode = 0)
    {
        $where['mode'] = $mode;
        if (!empty($user_id)) {
            if (is_array($user_id)) {
                $where['user_id'] = ['in', $user_id];
            } else {
                $where['user_id'] = ['eq', $user_id];
            }
        }
        if (!empty($department_id)) {
            if (is_array($department_id)) {
                $where['department_id'] = ['in', $department_id];
            } else {
                $where['department_id'] = ['eq', $department_id];
            }
        }
        $mapList = $this->departmentUserMapModel->field(true)->where($where)->select();
        return $mapList;
    }

    /**
     * 获取用户所在部门信息
     * @param $user_id
     * @return array
     */
    public function getDepartmentByUserId($user_id)
    {
        $mapList = $this->getMap($user_id);
        $department_ids = [];
        foreach ($mapList as $key => $value) {
            array_push($department_ids, $value['department_id']);
        }
        return $department_ids;
    }

    /**
     * 获取部门用户信息
     * @param int $department_id
     * @param string $name
     * @param int $mode
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws Exception
     */
    public function getUsers($department_id=0,$name='',$mode = 0)
    {
        $mapList = [];
        if($department_id){
            $department = (new DepartmentServer)->info($department_id);
            if ($department['is_bottom'] != 1) {
                throw new JsonErrorException('该部门不是末端部门', 400);
            }
            $mapList = $this->getMap(0, $department_id,$mode);
        }else if($name){
            $where['realname'] = ['like','%'.$name.'%'];
            $user_id = (new User())->where($where)->column('id');
            $mapList = $this->getMap($user_id, 0,$mode);
        }

        $user_ids = [];
        foreach ($mapList as $key => $value) {
            array_push($user_ids, $value['user_id']);
        }
        $target = (new MonthlyTargetAmountService())->getTarget($user_ids,0,'','',$mode);

        foreach ($mapList as &$user) {
            $user['target_amount'] = $target[$user['user_id']] ?? 0;
            if($mode == MonthlyModeConst::mode_development){
               $user['target_amount'] = str_replace('.00','',$user['target_amount']);
            }
            $this->showUser($user);
        }
        return $mapList;
    }

    /**
     * 获取部门负责人
     * @param $department_id 【部门id】
     * @return array
     */
    public function getLeader($department_id)
    {
        $leader_ids = [];
        $leader = (new DepartmentModel)->where(['id' => $department_id])->value('leader_id');
        if ($leader) {
            $leader_ids = json_decode($leader, true);
        }
        return $leader_ids;
    }

    /**
     * 获取部门底部所有的组
     * @param $department_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSonDepartmentByDepartmentId($departments, $department_id, &$all_department_ids)
    {
        if (isset($departments[$department_id])) {
            if (isset($departments[$department_id]['child_ids']) && !empty($departments[$department_id]['child_ids'])) {
                $department_ids = $departments[$department_id]['child_ids'];
                $all_department_ids = array_merge($all_department_ids, $department_ids);
                foreach ($department_ids as $k => $id) {
                    $this->getSonDepartmentByDepartmentId($departments, $id, $all_department_ids);
                }
            }
        }
    }

    /**
     * 获取类型为部门的部门ID
     * @param $department_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentTypeByDepartmentId($department_id)
    {
        $departmentInfo = (new DepartmentModel)->field('id,pid,type')->where(['id' => $department_id])->find();
        if (!empty($departmentInfo)) {
            if ($departmentInfo['type'] == MonthlyDepartmentTypeService::DEPARTMENT) {
                return $departmentInfo['id'];
            } else {
                return $this->getDepartmentTypeByDepartmentId($departmentInfo['pid']);
            }
        }
    }

    /**
     * 建立 部门与人员的关系
     * @param $user_id
     * @param $department
     * @param int $mode
     * @return array
     * @throws Exception
     */
    public function add($user_id, $department,$mode = 0)
    {
        if (empty($user_id) || empty($department)) {
            throw new Exception('用户与部门都不能为空');
        }
        if (!is_array($user_id)) {
            $user_id = [$user_id];
        }
        $time = time();
        Db::startTrans();
        try {
            foreach ($user_id as $key => $value) {
                $data['user_id'] = $value;
                $data['department_id'] = $department;
                $data['mode'] = $mode;
                $data['status'] = 0;
                $data['create_time'] = $time;
                $data['update_time'] = $time;
                MonthlyTargetLog::AddLog(MonthlyTargetLog::user,$department,$data['user_id'],'加入到'.(new DepartmentServer())->getDepartmentNames($department));
                (new DepartmentUserMap())->allowField(true)->isUpdate(false)->save($data);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage());
        }
        return ['message' => '添加成功'];
    }


    /**
     * 删除部门与用户的关系
     * @param $user_id
     */
    public function delete($user_id)
    {
        $this->departmentUserMapModel->where(['user_id' => $user_id])->delete();
    }

    /**
     * 更新用户信息
     * @param $params
     * @param $where
     * @param array $info
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update($params, $where,$info = [])
    {

        $data = [
            'update_time' => time()
        ];
        if(!$info){
            $info = $this->departmentUserMapModel->where($where)->find();
            if (empty($info)) {
                return json(['status' => false,'message' => '该记录不存在']);
            }
        }



        $statusMsg = ['启用','停用'];
        $msg = '';



        if(isset($params['pid']) && $params['pid'] != $info['department_id']){
            $new = (new DepartmentModel())->where('id',$params['pid'])->value('is_bottom');
            if(!$new){
                return ['status' => false,'message' => '新部门必须是末端小组'];
            }

            $departmentTree = Cache::store('MonthlyDepartment')->getMonthlyDepartmentTree();
            $info['department_id'] =  $data['department_id'] = $params['pid'];
            $msg .= ',部门:'.($departmentTree[$info['department_id']] ?? '').'-->'.($departmentTree[$data['department_id']] ?? '');
        }


        if (isset($params['status']) && $params['status'] != $info['status']) {
            $data['status'] = $params['status'];
            $msg .= '状态:'.$statusMsg[$info['status']].'-->'.$statusMsg[$data['status']];
        }

        if(!$msg){
            return ['status' => false,'message' => '该记录状态无变化'];
        }


        Db::startTrans();
        try {
            MonthlyTargetLog::AddLog(MonthlyTargetLog::user,$info['department_id'],$info['user_id'],$msg);
            $this->departmentUserMapModel->save($data, ['id' => $info['id']]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return ['status' => false, 'message' => $e->getMessage() . $e->getFile() . $e->getLine()];
        }
        return ['status' => true, 'message' => '更新成功'];


    }


    /**
     * 删除绑定关系
     * @param $id
     * @param int $mode
     * @return array
     */
    public function deleteUser($id,$mode = 0)
    {
        Db::startTrans();
        try {
            $where['mode'] = $mode;
            $where['user_id'] = $id;
            $this->departmentUserMapModel->where($where)->delete();
            MonthlyTargetLog::AddLog(MonthlyTargetLog::user,0,$id,'删除用户绑定数据');
            $whereA['mode'] = $mode;
            $whereA['relation_id'] = $id;
            $whereA['type'] = 0;
            $whereA['year'] = date('Y');
            $whereA['monthly'] = date('m');
            (new MonthlyTargetAmount())->where($whereA)->delete();
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return ['status' => false, 'message' => $e->getMessage() . $e->getFile() . $e->getLine()];
        }
        return true;
    }

    /**
     * 输出用户信息
     * @param $user
     * @throws Exception
     */
    public function showUser(&$user)
    {
        $userInfo = Cache::store('user')->getOneUser($user['user_id']);
        $user_name = $userInfo['realname'] ?? '';
        $user['id'] = $user['user_id'];
        $user['pid'] = $user['department_id'];
        $user['name'] = $user_name;
        $user['job_number'] = $userInfo['job_number'] ?? '';
        $user['leader_id'] = $user['id'];
        $user['leader_name'] = $user_name;
        $user['parents'] = [];
        $user['depth'] = [];
        $user['name_path'] = [];
        $user['child_ids'] = [];
        $user['is_bottom'] = 1;
        $user['type'] = 2;
    }

    /**
     * 读取用户信息
     * @param $user_id
     * @param string $monthly
     * @param string $year
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info($user_id, $monthly = '', $year = '')
    {
        if (empty($user_id)) {
            throw new Exception('用户不能为空');
        }
        $user = $this->departmentUserMapModel->where(['user_id' => $user_id])->find();
        $this->showUser($user);
        $target = (new MonthlyTargetAmountService())->getTarget($user_id, 0, $monthly, $year);
        $user['target_amount'] = $target[$user_id] ?? 0;
        return $user;
    }

    /**
     *  拉取部门以及上级信息
     * @param $department_id
     * @return array
     */
    public function getPidDepartment($department_id)
    {

        $server = new DepartmentServer();
        $department = $server->getDepartment($department_id);
        $reData = [
            'department_id' => $department_id,
            'name' => $department['name'],
            'status' => $department['status'],
            'pid' => $department['pid'],
            'leader_id' => $department['leader_id'],
            'leader_name' => $department['leader_name'],
            'pid_name' => '',
        ];
        if($reData['pid']){
            $department = $server->getDepartment($reData['pid']);
            $reData['pid_name'] = $department['name'];
        }
        return $reData;
    }


}