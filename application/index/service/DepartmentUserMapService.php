<?php

namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\Account;
use app\common\model\AccountUserMap;
use app\common\model\DepartmentUserMap;
use app\common\model\ServerLog;

use app\common\model\Department as ModelDepartment;
use app\common\service\Common;
use think\Db;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/18
 * Time: 10:34
 */
class DepartmentUserMapService
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
     * @param array $jobIds 职位id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getMap($user_id = 0, $department_id = 0, $jobIds = [])
    {
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

        // 返回指定职务的leader列表  add by 2019-04-10
        (is_array($jobIds) && !empty($jobIds)) && $where['job_id'] = ['in', $jobIds];

        if (isset($where)) {
            $mapList = $this->departmentUserMapModel->field(true)->where($where)->select();
        } else {
            $mapList = [];
        }
        return $mapList;
    }

    /**
     * @title 或者该部门下的专员
     * @author starzhan <397041849@qq.com>
     */
    public function getMapCommissioner($department_id)
    {
        if (!empty($department_id)) {
            if (is_array($department_id)) {
                $where['department_id'] = ['in', $department_id];
            } else {
                $where['department_id'] = ['eq', $department_id];
            }
        }
        if (isset($where)) {
            $where['job_id'] = 20;//专员
            $mapList = $this->departmentUserMapModel->field(true)->where($where)->select();
        } else {
            $mapList = [];
        }
        return $mapList;
    }

    /**
     * @title 获取该部门下所有的人员，根据职位做限制
     * @param $department_id
     * @param array $wordIds
     * @author starzhan <397041849@qq.com>
     */
    public function getMapByWordIds($department_id, $job_id = [])
    {
        if (!empty($department_id)) {
            if (is_array($department_id)) {
                $where['department_id'] = ['in', $department_id];
            } else {
                $where['department_id'] = ['eq', $department_id];
            }
        }
        if (isset($where)) {
            if ($job_id) {
                $where['job_id'] = ['in', $job_id];
            }
            $mapList = $this->departmentUserMapModel->field(true)->where($where)->select();
        } else {
            $mapList = [];
        }
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
     * @param $department_id
     * @param $job
     * @return array
     */
    public function getUserByDepartmentId($department_id, $job = false)
    {
        $mapList = $this->getMap(0, $department_id);
        $user_ids = [];
        if ($job) {
            foreach ($mapList as $key => $value) {
                $userInfo = Cache::store('User')->getOneUser($value['user_id'], 'job');
                if (isset($userInfo['job']) && $job == $userInfo['job']) {
                    array_push($user_ids, $value['user_id']);
                }
            }
        } else {
            foreach ($mapList as $key => $value) {
                array_push($user_ids, $value['user_id']);
            }
        }
        return $user_ids;
    }

    /**
     * @title 获取该部门下所有的专员信息
     * @param $department_id
     * @return array
     * @author starzhan <397041849@qq.com>
     */

    public function getCommissionerByDepartmentId($department_id)
    {
        $mapList = $this->getMapCommissioner($department_id);
        $user_ids = [];
        foreach ($mapList as $key => $value) {
            $userInfo = Cache::store('user')->getOneUser($value['user_id']);
            if (empty($userInfo)) {
                continue;
            }
            if ($userInfo['on_job'] == 0) {
                continue;
            }
            if ($userInfo['status'] == 0) {
                continue;
            }
            array_push($user_ids, $value['user_id']);
        }

        return $user_ids;
    }

    /**
     * @title 获取该部门下所有对应职位的人员
     * @param $department_id
     * @param array $workId
     * @author starzhan <397041849@qq.com>
     */
    public function getUserByDepartmentIdAndWorkIds($department_id, $workId = [])
    {
        $mapList = $this->getMapByWordIds($department_id, $workId);
        $user_ids = [];
        foreach ($mapList as $key => $value) {
            $userInfo = Cache::store('user')->getOneUser($value['user_id']);
            if (empty($userInfo)) {
                continue;
            }
            if ($userInfo['on_job'] == 0) {
                continue;
            }
            if ($userInfo['status'] == 0) {
                continue;
            }
            array_push($user_ids, $value['user_id']);
        }
        return $user_ids;
    }

    /**
     * @title 获取部门下面的人员信息 id，姓名
     * @param $department_id
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getUserInfoByDepartmentId($department_id)
    {

        $result = [];
        $mapList = $this->getMap(0, $department_id);
        foreach ($mapList as $key => $value) {
            $row = [];
            $row['value'] = (int)$value['user_id'];
            $row['label'] = Cache::store('user')->getOneUserRealname($value['user_id']);
            $result[] = $row;
        }
        return $result;

    }

    /**
     * 获取部门负责人
     * @param $department_id 【部门id】
     * @param array $jobIds 【职位id】
     * @return array
     */
    public function getLeader($department_id, $jobIds = [])
    {
        $mapList = $this->getMap(0, $department_id, $jobIds);

        $leader_ids = [];
        foreach ($mapList as $key => $value) {
            if ($value['is_leader'] == 1) {
                array_push($leader_ids, $value['user_id']);
            }
        }
        return $leader_ids;
    }

    public function getLeaderByDepartmentType($department_id)
    {
        $ModelDepartmentUserMaps = new DepartmentUserMap();
        $mapList = $ModelDepartmentUserMaps->alias('m')
            ->join('department d', 'd.id=m.department_id', 'left')
            ->field("m.department_id,m.user_id,m.job_id,m.is_leader,d.type")
            ->where('m.department_id', $department_id)
            ->select();
        $leader_ids = [];
        foreach ($mapList as $key => $value) {
            if ($value['is_leader'] == 1) {
                switch ($value['type']) {
                    case 1:
                        if ($value['job_id'] == 18) {
                            array_push($leader_ids, $value['user_id']);
                        }
                        break;
                    case 2:
                        if ($value['job_id'] == 16) {
                            array_push($leader_ids, $value['user_id']);
                        }
                        break;
                    case 3:

                        break;
                    case 4:
                        if ($value['job_id'] == 16) {
                            array_push($leader_ids, $value['user_id']);
                        }
                        break;

                }
            }
        }
        return $leader_ids;

    }


    /**
     * @title 获取职位id
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function getWorkId($user_id)
    {
        $aTempList = $this->departmentUserMapModel->field('job_id')
            ->where('user_id', $user_id)
            ->where('is_leader', 1)
            ->select();
        $result = [];
        foreach ($aTempList as $v) {
            $result[] = $v['job_id'];
        }
        return $result;
    }

    /**
     * @title 根据职位id获取有什么用户
     * @param $job_id
     * @param int $is_leader
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getUserIdByJobId($job_id, $is_leader = 1)
    {
        $result = [];
        $ret = $this->departmentUserMapModel->where('job_id', $job_id)
            ->where('is_leader', $is_leader)
            ->field('user_id')
            ->select();
        foreach ($ret as $v) {
            $result[] = $v['user_id'];
        }
        return $result;
    }

    /**
     * 获取销售主管
     * @param $department_id [部门id]
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDirector($department_id)
    {
        $department_id = $this->getDepartmentTypeByDepartmentId($department_id);
        $mapList = $this->getMap(0, $department_id);
        $director_ids = [];
        foreach ($mapList as $key => $value) {
            if ($value['job_id'] == 17) {
                array_push($director_ids, $value['user_id']);
            }
        }
        return $director_ids;
    }

    /**
     * 获取销售组长
     * @param $department_id [部门id]
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGroupLeaderByChannel($department_id, $is_top = true)
    {
        if ($is_top) {
            $departments = Cache::store('department')->tree();
            //支持数组
            if (is_array($department_id)) {
                $all_department_ids = $department_id;
                foreach ($department_id as $id) {
                    $this->getSonDepartmentByDepartmentId($departments, $id, $all_department_ids);
                }
            } else {
                $all_department_ids = [$department_id];
                $this->getSonDepartmentByDepartmentId($departments, $department_id, $all_department_ids);
            }
            $department_id = $all_department_ids;
        } else {
            $department_id = $this->getDepartmentTypeByDepartmentId($department_id);
        }
        $mapList = $this->getMap(0, $department_id);
        // dump($mapList); 
        $director_ids = [];
        foreach ($mapList as $key => $value) {
            //18
            if ($value['job_id'] == 18) {
                array_push($director_ids, $value['user_id']);
            }
        }
        // dump($director_ids);
        return $director_ids;

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
        $departmentInfo = (new \app\common\model\Department())->field('id,pid,type')->where(['id' => $department_id])->find();
        if (!empty($departmentInfo)) {
            if ($departmentInfo['type'] == DepartmentTypeService::DEPARTMENT) {
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
     * @throws Exception
     */
    public function add($user_id, $department)
    {
        if (empty($user_id) || empty($department)) {
            throw new Exception('用户与部门都不能为空');
        }
        if (!is_array($department)) {
            $department = [$department];
        }
        Db::startTrans();
        try {
            //删除这个用户的所在部门
            $this->delete($user_id);
            foreach ($department as $key => $value) {
                $data['user_id'] = $user_id;
                $data['department_id'] = $value['id'];
                $data['job_id'] = $value['job_id'];
                $this->departmentUserMapModel->allowField(true)->isUpdate(false)->save($data);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 设置部门负责人
     * @param $user_id
     * @param $department_id
     * @param $job_id
     * @param bool|false $is_delete_leader
     * @throws Exception
     */
    public function setLeader($user_id, $department_id, $job_id, $is_delete_leader = false)
    {
        if (empty($user_id) || empty($job_id)) {
            throw new Exception('部门负责人不能为空');
        }
        try {
            if ($is_delete_leader) {
                //删除之前部门的负责人
                $this->departmentUserMapModel->where(['department_id' => $department_id, 'is_leader' => 1])->delete();
            }
            $data['user_id'] = $user_id;
            $data['department_id'] = $department_id;
            $data['job_id'] = intval($job_id);
            $data['is_leader'] = 1;
            //判断用户是否已经存在
            $info = $this->departmentUserMapModel->where([
                'user_id' => $user_id,
                'department_id' => $department_id
            ])->find();
            if (empty($info)) {
                $this->departmentUserMapModel->allowField(true)->isUpdate(false)->save($data);
            } else {
                $this->departmentUserMapModel->where(['user_id' => $user_id, 'department_id' => $department_id])->update($data);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     * 自动关联 账号资料成员信息
     * @param $departmentId
     * @param $leader [新用户列表]
     * @param array $user
     * @return bool
     * @throws Exception
     */
    public function setLeaderAccountUser($departmentId, $leader, $oldLeader = [], $user = [])
    {
        // 1.查找出需要添加 和 删除的 用户ID
        $addUser = [];
        $delUser = [];
        $newLeader = [];
        foreach ($leader as $key => $value) {
            $newLeader[] = $value['user_id'];
            $addUser[] = $value['user_id'];
        }
        foreach ($oldLeader as $old) {
            if (!in_array($old, $newLeader)) {
                $delUser[] = $old;
            }
        }
        return $this->updateLeaderServer($departmentId, $addUser, $delUser, $user);
    }

    /**
     * 账号资料成员信息
     * @param $departmentId
     * @param $addUser
     * @param array $delUser
     * @return bool
     * @throws Exception
     */
    public function updateLeaderServer($departmentId, $addUser, $delUser = [], $user = [])
    {
        $accountUserMapServer = new AccountUserMapService();
        // 4.删除
        $accountList = $this->getUpdateAccountList($departmentId);
        $runData = [];
        foreach ($accountList as $id => $server_id) {
            $runData[$id] = [
                'server' => $server_id,
                'delUser' => $delUser,
                'addUser' => [],
            ];
        }
        // 5.0 添加
        $accountList = $this->getUpdateAccountList($departmentId, true);
        foreach ($accountList as $id => $server_id) {
            if (isset($runData[$id])) {
                $runData[$id]['addUser'] = $addUser;
            } else {
                $runData[$id] = [
                    'server' => $server_id,
                    'delUser' => [],
                    'addUser' => $addUser,
                ];
            }
        }

        foreach ($runData as $id => $v) {
            $accountUserMapServer->updateAccountUserMapAll($id, $v['server'], $v['addUser'], $v['delUser'], $user);
        }

        return true;
    }

    /**
     * 查找需要更新的账号资料信息
     * @param $departmentId
     * @param bool $isDiffJob
     * @return array
     * @throws Exception
     */
    public function getUpdateAccountList($departmentId, $isDiffJob = false)
    {
        $api = new Department();
        // 查找部门下所有用户关联的账号资料ID
        $departmentUser = $api->getDepartmentUser($departmentId, $isDiffJob);
        $allUser = [];
        //移除助理绑定的账号
        $leaderList = [];
        if ($isDiffJob) {
            $departmentUserMapModel = new DepartmentUserMap();
            $leaderList = $departmentUserMapModel->where([
                'job_id' => '19',
                'department_id' => $departmentId,
                'is_leader' => 1
            ])->column('user_id');
        }

        foreach ($departmentUser as $v) {
            foreach ($v as $u) {
                if ($leaderList && in_array($u, $leaderList)) {
                    continue;
                }
                $allUser[$u] = $u;
            }
        }
        $accountList = [];
        if ($allUser) {
            $accountIds = (new AccountUserMap())->where('user_id', 'in', $allUser)->column('account_id');
            if ($accountIds) {
                $accountList = (new Account())->where('id', 'in', $accountIds)->column('server_id', 'id');
            }
        }
        return $accountList;
    }

    /**
     * @title 获取部门内每组有多少销售
     * @param $departId
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getPublishUserByDepartmentId($departId, $channel_id = 2)
    {

        $ChannelDistribution = new ChannelDistribution();
        $channelSetting = $ChannelDistribution->read($channel_id);
        $position = $channelSetting['allow_position'];
        $result = $this->getSalesByDepartmentId($departId, $channel_id, $position);
        return $result;
    }

    /**
     * @title 获取该部门下有多少销售
     * @param $departmentId
     * @param int $channel_id
     * @param array $allowPosition
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getSalesByDepartmentId($departmentId, $channel_id = 0, $allowPosition = [])
    {
        $result = [];
        $tree = Cache::store('department')->tree();
        if (!isset($tree[$departmentId])) {
            return [];
        }
        $thisDepartInfo = $tree[$departmentId];
        if ($thisDepartInfo['job'] != 'sales') {
            return [];
        }
        if ($channel_id) {
            if ($thisDepartInfo['channel_id'] != $channel_id) {
                return [];
            }
        }
        $result['id'] = $departmentId;
        $result['name'] = $thisDepartInfo['name'];
        $result['users'] = $this->getUserByDepartmentIdAndWorkIds($departmentId, $allowPosition);
        if ($thisDepartInfo['child_ids']) {
            $result['child'] = $this->createChildUser($tree, $thisDepartInfo['child_ids'], $channel_id, $allowPosition);
        } else {
            $result['child'] = [];
        }
        return $result;
    }

    private function createChildUser($tree, $childIds = [], $channel_id = 0, $workId = [])
    {
        $data = [];
        foreach ($childIds as $id) {
            if (!isset($tree[$id])) {
                continue;
            }
            $info = $tree[$id];
            if ($info['job'] != 'sales') {
                continue;
            }
            if ($channel_id) {
                if ($info['channel_id'] != $channel_id) {
                    continue;
                }
            }
            $row = [];
            $row['id'] = $id;
            $row['name'] = $info['name'];
            $row['type'] = $info['type'];
            $row['users'] = $this->getUserByDepartmentIdAndWorkIds($id, $workId);
            if ($info['child_ids']) {
                $row['child'] = $this->createChildUser($tree, $info['child_ids'], $channel_id, $workId);
            } else {
                $row['child'] = [];
            }
            $data[] = $row;
        }
        return $data;
    }

    private function createChildUserInfo($tree, $childIds = [])
    {
        $data = [];
        foreach ($childIds as $id) {
            if (!isset($tree[$id])) {
                continue;
            }
            $info = $tree[$id];
            if ($info['job'] != 'sales' && $info['job'] != '') {
                continue;
            }
            $row = [];
            $row['value'] = (string)$id;
            $row['label'] = $info['name'];
            if ($info['child_ids']) {
                $row['children'] = $this->createChildUserInfo($tree, $info['child_ids']);
            } else {
                $row['children'] = $this->getUserInfoByDepartmentId($id);;
            }
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @title 每日刊登专用
     * @param $channel_id
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function departmentUserByChannelId($channel_id)
    {
        $userService = new User();
        $userInfo = Common::getUserInfo();
        $user_id = $userInfo['user_id'];
        $loginUser = $userService->getUserPositionByLoginUser($user_id);
        $isAdmin = (new Role())->isAdmin($user_id);
        if (!$isAdmin && empty($loginUser['word_id'])) {
            return [];
        }
        if (empty($loginUser['department_ids'])) {
            return [];
        }
        $ChannelService = new ChannelService();
        $proportionDepartment = $ChannelService->getProportionByChannelId($channel_id);
        $departmentLevel1 = [];
        foreach ($proportionDepartment as $v) {
            $row = [];
            $row['id'] = $v['department_id'];
            $row['name'] = $v['department_name'];
            $departmentLevel1[] = $row;
        }

        $tree = Cache::store('department')->tree();
        $result = [];
        foreach ($departmentLevel1 as $departmentInfo) {
            $row = [];
            $row['value'] = (string)$departmentInfo['id'];
            $row['label'] = $departmentInfo['name'];
            if (!isset($tree[$row['value']])) {
                continue;
            }
            $treeInfo = $tree[$row['value']];
            if ($treeInfo['child_ids']) {
                $row['children'] = $this->createChildUserInfo($tree, $treeInfo['child_ids']);
            }
            $result[] = $row;
        }
        if($isAdmin){
            return $result;
        }
        $fillData = $this->findDepartmentAndSaleLevel($tree, $loginUser['department_ids']);
        $result = $this->fillDepartmentAndSaleByLevel($result, $fillData);
        return $result;
    }


    /**
     * @title 找出当前部门所在级别
     * @param $result
     * @param array $departmentIds
     * @param array $level
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    private function findDepartmentAndSaleLevel($tree, $departmentIds = [],$result=[])
    {
        $result = [];
        foreach ($departmentIds as $departmentId) {
            if ($departmentIds) {
                if (!isset($tree[$departmentId])) {
                    continue;
                }
            }
            $currentDepartment = $tree[$departmentId];
            $parent = $currentDepartment['parents'];
            $parent[] = $departmentId;
            $result = array_merge($result, $parent);
            if($currentDepartment['child_ids']){
                $result = array_merge($result, $currentDepartment['child_ids']);
                $tmp = $this->findDepartmentAndSaleLevel($tree,$currentDepartment['child_ids'],$result);
                if($tmp){
                    $result = array_merge($result, $tmp);
                }
            }
        }
        return array_unique($result);
    }

    /**
     * @title 根据找出的级别，得出筛选后的部门id
     * @param $result
     * @param $level
     * @param array $data
     * @author starzhan <397041849@qq.com>
     */
    public function fillDepartmentAndSaleByLevel(&$result, $fillData = [])
    {

        foreach ($result as $k => &$v) {
            if (!is_string($v['value'])) {
                continue;
            }
            if (!in_array($v['value'], $fillData)) {
                unset($result[$k]);
                continue;
            }
            if ($v['children']) {
                $this->fillDepartmentAndSaleByLevel($v['children'], $fillData);
            }
        }
        $result = array_merge($result,[]);
        return $result;
    }

    /**
     * 矫正部门负责人所管理的账号（添加没有的，移除多余的）
     * @param $departmentId
     * @param bool $isForce
     * @throws Exception
     */
    public function updateDepartmentUserMap($departmentId, $isForce = true)
    {
        $user = [
            'user_id' => 0,
            'realname' => '[系统矫正部门]系统',
            'username' => '系统',
        ];

        //1.查找该部门控制的账号
        $accountDepartment = $this->getUpdateAccountList($departmentId, $isForce);
        //2.查找部门负责人已绑定的账号
        $leaders = $this->getLeader($departmentId);
        $accountUserMapServer = new AccountUserMapService();
        $runData = [];
        foreach ($leaders as $userId) {
            $accountBinding = $accountUserMapServer->userBingdingAccountIds($userId);
            //3.对比差异，添加或者删除账号
            $delAccountIds = array_diff($accountBinding, $accountDepartment);
            $this->updateAccountUser($delAccountIds, $userId, false, $runData);
            $addAccountIds = array_diff($accountDepartment, $accountBinding);
            $this->updateAccountUser($addAccountIds, $userId, true, $runData);
        }

        $accountUserMapServer = new AccountUserMapService();
        foreach ($runData as $id => $v) {
            $accountUserMapServer->updateAccountUserMapAll($id, $v['server'], $v['addUser'], $v['delUser'], $user);
        }
        return true;
    }

    /**
     * 添加或者删除账号
     * @param $accountIds
     * @param $userId
     * @param $isAdd
     * @param array $user
     * @return bool
     * @throws Exception
     */
    private function updateAccountUser($accountIds, $userId, $isAdd, &$runData = [])
    {
        if (!$accountIds) {
            return false;
        }
        $accountList = (new Account())->where('id', 'in', $accountIds)->column('server_id', 'id');
        $type = 'delUser';
        if ($isAdd) {
            $type = 'addUser';
        }
        foreach ($accountList as $id => $server_id) {
            if (isset($runData[$id])) {
                $runData[$id][$type][] = $userId;
            } else {
                $runData[$id] = [
                    'server' => $server_id,
                    'delUser' => [],
                    'addUser' => [],
                ];
                $runData[$id][$type][] = $userId;
            }
        }

    }

    /**
     * @title 获取对应渠道下某些职位的销售人员
     * @param array $aChannelIds
     * @param array $aJobIds
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getJobSalespersonByChannelIds(array $aChannelIds, array $aJobIds = [])
    {
        if (empty($aChannelIds) || empty($aJobIds)) {
            return [];
        }
        $modelDepartmentMapUser = new DepartmentUserMap();
        $userIds = $modelDepartmentMapUser->alias('m')
            ->join('department d', 'm.department_id=d.id', 'left')
            ->where('d.channel_id', 'in', $aChannelIds)
            ->where('d.job', 'sales')
            ->where('m.job_id', 'in', $aJobIds)
            ->column('m.user_id');

        return $userIds;
    }

}