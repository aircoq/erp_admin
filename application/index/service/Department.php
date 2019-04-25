<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-2-27
 * Time: 上午10:28
 */

namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use \app\common\model\Department as DepartmentModel;
use app\common\model\DepartmentUserMap;
use app\common\service\ChannelAccountConst;
use app\common\service\ChannelConst;
use app\index\service\DepartmentTypeService;
use think\Db;
use think\Exception;

class Department
{
    private $departments = [];

    public function __construct()
    {
        $this->departments = Cache::store('department')->getDepartment();
    }

    public function getDepartment($id)
    {
        return Cache::store('department')->getDepartment($id);
    }

    public function getDepartments()
    {
        return $this->departments;
    }

    /**
     * @param $channel_id
     * @param null $call
     * @return array
     */
    public function getDepsByChannel($channel_id, $call = null)
    {
        $model = new DepartmentModel();
        $name = ChannelConst::channelId2Name($channel_id);
        $dep = $model->whereLike('name', "%{$name}%")->find();
        function gets($model, $dep, &$ret)
        {
            $childs = $model->where('pid', $dep->id)->select();
            foreach ($childs as $child) {
                array_push($ret, $child);
                gets($model, $child, $ret);
            }
        }

        $ret = [];
        if ($dep) {
            array_push($ret, $dep);
            gets($model, $dep, $ret);
        }
        $result = [];
        if ($call) {
            foreach ($ret as $r) {
                $result[] = call_user_func($call, $r);
            }
        } else {
            $result = $ret;
        }
        return $result;
    }

    public function getDepartmentLayout($depId, $callback)
    {
        $result = [];
        $this->concatParentDepartments($depId, $this->departments, $result, $callback);
        return array_reverse($result);
    }

    public function getDepartmentNames($depId)
    {
        $names = $this->getDepartmentLayout($depId, function ($dep) {
            return $dep['name'];
        });
        if ($names > 0) {
            array_shift($names);
            return join("->", $names);
        } else {
            return "没有定义分组";
        }
    }

    private function concatParentDepartments($depId, $deparetments, &$result, $callback)
    {
        if ($dep = param($deparetments, $depId)) {
            $result[] = $callback($dep);
            $this->concatParentDepartments($dep['pid'], $deparetments, $result, $callback);
        }
    }

    /**
     * @author phill
     */
    public function company()
    {
        $departmentModel = new DepartmentModel();
        $list = $departmentModel->field('id,name')->where(['pid' => 0])->whereOr(['type' => 1])->select();
        return $list;
    }

    /**
     * 详情信息
     * @param $id
     * @return mixed
     * @throws \think\Exception
     */
    public function info($id)
    {
        $department = $this->getDepartment($id);
        $departmentUserMapModel = new DepartmentUserMap();
        $leaderList = $departmentUserMapModel->field('user_id,job_id')->where([
            'department_id' => $id,
            'is_leader' => 1
        ])->select();
        if (!empty($department)) {
            if ($department['pid'] == 0) {
                $parDepartment = $this->getDepartment($id);
            } else {
                $parDepartment = $this->getDepartment($department['pid']);
            }
            $department['leader_id'] = $leaderList;
            $department['department'] = $parDepartment['name'] ?? '';

            // 返回部门标签 add by 2019-4-12
            $department['tag_name'] = (new DepartmentTagMapService())->get($id);
        }
        return $department;
    }

    /**
     * 排序
     * @param $sort
     */
    public function sort($sort)
    {
        $departmentModel = new DepartmentModel();
        Db::startTrans();
        try {
            foreach ($sort as $k => $v) {
                $departmentModel->where(['id' => $v['id']])->update(['pid' => $v['pid']]);
            }
            Db::commit();
            Cache::store('department')->delete();
            // 该清楚缓存的方法已放入上面delete（）方法中 mdf 2019-04-08
            //Cache::handler()->del('cache:department_tree');
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 获取部门下所有的用户信息
     * @param $department_id
     * @param $isDiffJob
     * @return array
     * @throws Exception
     */
    public function getDepartmentUser($department_id,$isDiffJob = false)
    {
        $departments = Cache::store('department')->tree();

        $departmentUserMapService = new DepartmentUserMapService();
        $userData = [];
        if (isset($departments[$department_id])) {
            $child_ids = $departments[$department_id]['child_ids'];
            if($isDiffJob){
                $isDiffJob = $departments[$department_id]['job'];
                $allJob = ['sales','customer']; //目前只支持销售与开发
                if(!in_array($isDiffJob,$allJob)){
                    $isDiffJob = false;
                }
            }

            if (!empty($child_ids)) {
                foreach ($child_ids as $k => $child) {
                    $user_ids = $departmentUserMapService->getUserByDepartmentId($child, $isDiffJob);
                    array_push($userData, $user_ids);
                }
            } else {
                $user_ids = $departmentUserMapService->getUserByDepartmentId($department_id, $isDiffJob);
                array_push($userData, $user_ids);
            }
        }
        return $userData;
    }

    /**
     * 通过部门结构查找部门id
     * @param $department_name
     * @param string $split
     * @return int|mixed
     */
    public function parsingDepartment($department_name, $split = '>>')
    {
        $departmentModel = new DepartmentModel();
        $departmentArr = explode($split, $department_name);
        $pid = 0;
        foreach ($departmentArr as $k => $name) {
            $where['name'] = ['eq', $name];
            $where['pid'] = ['eq', $pid];
            $departmentInfo = $departmentModel->field('id')->where($where)->find();
            $pid = $departmentInfo['id'] ?? $pid;
        }
        return $pid;
    }

    /**
     * 获取特定职务的部门信息
     * @param $job
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentInfoByJob($job)
    {
        $where['job'] = ['eq', $job];
        if (is_array($job)) {
            $where['job'] = ['in', $job];
        }
        $departmentModel = new DepartmentModel();
        $departmentList = $departmentModel->field('id,name')->where($where)->select();
        $data = array_map(function ($info) {
            return $info->toArray();
        }, $departmentList);
        return $data;
    }

    /**
     * 岗位的部门(分组)ID集合
     * @param string $job
     * @param bool $top 是否顶级部门
     * @return array
     */
    public function getDepartmentIds($job = 'sales', $top = false)
    {
        $where = [
            'job' => $job,
            'status' => 1
        ];
        $top && $where['pid'] = 4;
        return DepartmentModel::where($where)->column('id');
    }

    /**
     * 部门关联关系
     * @param string $job
     * @return array
     */
    public function nodes($job = 'sales')
    {
        $ids = DepartmentModel::where('job', $job)->where('status', 1)->column('id,pid,name');
        $arr = [];
        foreach ($ids as $v) {
            $arr[$v['pid']][$v['id']] = ['id' => $v['id'], 'name' => $v['name']];
        }
        return $arr;
    }

    /**
     * @title 生成部门树状结构
     * @param int $top_ids
     * @param $nodes  eg:$this->>nodes()
     * @return array
     */
    public function tree($top_ids = 0, $nodes)
    {
        if (!is_array($top_ids)) {
            if (is_string($top_ids)) {
                $top_ids = explode(',', $top_ids);
            } else {
                $top_ids = [$top_ids];
            }
        }
        $tree = [];
        foreach ($top_ids as $pid) {
            if (isset($nodes[$pid])) {
                foreach ($nodes[$pid] as $k => $v) {
                    $v['nodes'] = $this->tree($v['id'], $nodes);
                    $tree[$k] = $v;
                }
            }
        }
        return $tree;
    }

    /**
     * 获取平台组长信息（暂时方法）
     * @param $channel_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGroupLeaderByChannel($channel_id)
    {
        $data = [];
        switch ($channel_id) {
            case ChannelAccountConst::channel_ebay:
                $data = (new DepartmentUserMapService())->getGroupLeaderByChannel([332,451,452,453,163]);
                break;
            case ChannelAccountConst::channel_amazon:
                $data = (new DepartmentUserMapService())->getGroupLeaderByChannel([141, 396, 224, 353]);
                $data = array_unique($data);
                break;
            case ChannelAccountConst::channel_wish:
                $data = (new DepartmentUserMapService())->getGroupLeaderByChannel(225);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $data = (new DepartmentUserMapService())->getGroupLeaderByChannel([463, 464, 476]);
                break;
            case ChannelAccountConst::channel_Joom:
                $data = (new DepartmentUserMapService())->getGroupLeaderByChannel([483, 484]);
                break;
            case ChannelAccountConst::channel_Pandao:
                $data = (new DepartmentUserMapService())->getGroupLeaderByChannel(52);
                break;
        }
        return $data;
    }

    /**
     * @title 获取日志列表
     * @param $id
     * @return mixed
     * @author starzhan <397041849@qq.com>
     */
    public function getLogList($id)
    {
        $result = (new DepartmentLog())->getLog($id);
        return $result;
    }

    /**
     * @title 根据平台id获取类型为部门，职能为销售的部门
     * @param $channel_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentByChannelId($channel_id)
    {
        $model = new DepartmentModel();
        return $model->where('type', DepartmentTypeService::DEPARTMENT)
            ->where('job', 'sales')
            ->where('channel_id', $channel_id)
            ->field('id,name')
            ->select();
    }


    private function getNextLevelDepartment($tree, $childIds, $channel_id)
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
            if ($info['channel_id'] != $channel_id) {
                continue;
            }
            $row = [];
            $row['department_id'] = (string)$id;
            $row['department_name'] = $info['name'];
            if ($info['child_ids']) {
                $row['child'] = $this->getNextLevelDepartment($tree, $info['child_ids'],$channel_id);
            }
            $data[] = $row;
        }
        return $data;
    }


    /**
     * @title 获取对应渠道的部门树
     * @author starzhan <397041849@qq.com>
     * @param $channel_id
     * @return array
     * @throws Exception
     */
    public function getDepartmentTreeByChannelId($channel_id)
    {
        $departmentService = new Department();
        $departmentLevel1 = $departmentService->getDepartmentByChannelId($channel_id);
        $tree = Cache::store('department')->tree();
        $result = [];
        foreach ($departmentLevel1 as $departmentInfo) {
            $row = [];
            $row['department_id'] = $departmentInfo['id'];
            $row['department_name'] = $departmentInfo['name'];
            if (!isset($tree[$row['department_id']])) {
                continue;
            }
            $treeInfo = $tree[$row['department_id']];
            if ($treeInfo['child_ids']) {
                $row['child'] = $this->getNextLevelDepartment($tree, $treeInfo['child_ids'],$channel_id);
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 获取当前平台的所有上级部门
     * @param $channel_id
     * @return array
     */
    public function getParentDepartmentsByChannelId($channel_id)
    {
        $model = new DepartmentModel();
        $has_pids = $model->where('channel_id', $channel_id)->distinct('pid')->column('pid');
        function getPids( $model, $has_pid, &$ret=[])
        {
            $pid = $model->where('id', $has_pid)->find();
            if(!empty($pid)){
                array_push($ret, $pid['id']);
                getPids($model, $pid->pid, $ret);
            }
        }
        $ret = [];
        $result = [];
        if ($has_pids) {
            foreach ( $has_pids as $has_pid) {
                getPids($model, $has_pid, $ret);
                $result[] = $ret;
            }
        }
        return $ret;
    }
}