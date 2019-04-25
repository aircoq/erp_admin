<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use service\dingding\DingApi;
use app\common\model\Department;
use app\common\model\User;
use app\common\model\UserMap;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/1
 * Time: 15:19
 */
class Ding extends AbsTasker
{
    protected $department;
    protected $user;
    protected $userMap;
    private $departments = [];

    public function __construct()
    {
        $this->department = new Department();
        $this->user = new User();
        $this->userMap = new UserMap();
    }
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '拉取钉钉数据';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return '宇';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 任务执行内容
     * @return void
     */
    public function execute()
    {
        $this->departments = $this->getDepartment();
        $this->saveDepartment();
        $this->saveUser();
    }

    /**
     * 获取部门列表
     * $departmentList : 钉钉部门列表信息
     */
    private function getDepartment()
    {
        $departmentList = [];
        $common = DingApi::instance()->loader('common');
        $result = $common->departmentList();
        if (isset($result['department'])) {
            $departmentList = $result['department'];
        }
        return $departmentList;
    }

    /**
     * 保存钉钉所有部门信息到本地数据库
     */
    private function saveDepartment()
    {
        try{
            echo $this->department->getTable();
            $code = Cache::store('department')->code();
            $newDepartment = [];
            $updateData = [];
            foreach ($this->departments as $k => $v) {
                $departmentId = $v['id'];
                $detail = $this->getDepartmentDetail($departmentId);
                if (!isset($v['parentid'])) {
                    $parentId = 0;
                    $department['pid'] = 0;
                } else {
                    $parentId = $v['parentid'];
                }
                $department = [
                    'name' => $v['name'],
                    'leader_id' => $detail['deptManagerUseridList'],
                    'create_time' => time(),
                    'remark' => 'DingDing API拉取',
                    'code' => isset($code['other']) ? $code['other']['code'] : 'other'
                ];
                $this->department->data($department)->isUpdate(false)->save();
                $id = $this->department->id;
                $newDepartment[$k] = [
                    'id' => $departmentId,
                    'table_id' => $id,
                    'parentid' => $parentId,
                    'name' => $v['name'],
                ];
            }
            foreach ($this->departments as $kk => $vv) {
                if (isset($vv['parentid'])) {
                    $tableId = $newDepartment[$kk]['table_id'];
                    $updateData[$kk]['id'] = $tableId;
                    if ($vv['parentid'] == 1) {
                        $updateData[$kk]['pid'] = 1;
                    } else {
                        foreach ($newDepartment as $m => $n) {
                            if ($vv['parentid'] == $n['id']) {
                                $updateData[$kk]['pid'] = $n['table_id'];
                                continue;
                            }
                        }
                    }
                }
            }
            $this->department->isUpdate(true)->saveAll($updateData);
        }catch (\Exception $e){
            var_dump($e->getMessage());
        }

    }

    public function getDepartmentDetail($depId)
    {
        $common = DingApi::instance()->loader('common');
        return $common->departmentDetail($depId);
    }

    /**
     * 保存钉钉所有用户到本地数据库
     */
    private function saveUser()
    {
        set_time_limit(0);
        $common = DingApi::instance()->loader('common');
        $salt = $this->user->getSalt();
        $mcryptPassword = $this->user->getHashPassword(111111, $salt);
        $time = time();
        //获取所有部门
        foreach ($this->departments as $k => $department) {
            $departmentId = $department['id'];
            $localDepartmentId = 0;
            if($dep = $this->department->where(['name'=>$department['name']])->find()){
                $localDepartmentId = $dep->id;
            }
            //获取当前部门下的所有员工
            $userList = $common->userList($departmentId);
            if ($userList && isset($userList['userlist'])) {
                $userList = $userList['userlist'];
                foreach ($userList as $kk => $vv) {
                    $data = $mapData = $mapWhere = [];
                    $username = $where = '';
                    if (isset($vv['email']) && !empty($vv['email'])) {
                        $username = $vv['email'];
                    } else {
                        if (isset($vv['orgEmail']) && !empty($vv['orgEmail'])) {
                            $username = $vv['orgEmail'];
                        }
                    }
                    if ($username) {
                        $where = "username = '$username' or ";
                    } else {
                        $username = $vv['mobile'];
                    }
                    $where .= " mobile = '$vv[mobile]'";
                    $user = $this->user->where($where)->find();
                    $data['username'] = $username;
                    $data['realname'] = $vv['name'];
                    $data['salt'] = $salt;
                    $data['password'] = $mcryptPassword;
                    $data['icon'] = $vv['avatar'];
                    $data['remark'] = 'dingding API拉取';
                    $data['email'] = $username;
                    $data['mobile'] = isset($vv['mobile']) ? $vv['mobile'] : 0;
                    $data['department_id'] = $localDepartmentId;
                    $mapData['username'] = $username;
                    $mapData['realname'] = $vv['name'];
                    $mapData['email'] = $username;
                    $mapData['mobile'] = isset($vv['mobile']) ? $vv['mobile'] : 0;
                    if ($user) {
                        $mapWhere['username'] = $username;
                        $mapWhere['mobile'] = $vv['mobile'];
                        $userMap = $this->userMap->where($mapWhere)->find();
                        if ($userMap) {
                            $mapData['update_time'] = $time;
                            $this->userMap->where($mapWhere)->update($mapData);
                        } else {
                            $mapData['user_id'] = $user['id'];
                            $mapData['create_time'] = $time;
                            $this->userMap->data($mapData)->isUpdate(false)->save();
                        }
                    } else {
                        $data['create_time'] = $time;
                        $this->user->data($data)->isUpdate(false)->save();
                        $id = $this->user->id;
                        $mapData['user_id'] = $id;
                        $mapData['create_time'] = $time;
                        $this->userMap->data($mapData)->isUpdate(false)->save();
                    }
                }
            }
        }
    }
}