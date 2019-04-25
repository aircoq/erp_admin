<?php
/**
 * Created by PhpStorm.
 * User: libaimoin
 * Date: 18-10-31
 * Time: 上午10:28
 */

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use \app\common\model\monthly\MonthlyTargetDepartment as DepartmentModel;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap as DepartmentUserMap;
use app\common\service\ChannelAccountConst;
use app\common\service\ChannelConst;
use think\Db;
use think\Exception;

class MonthlyTargetDepartmentService
{
    private $departments = [];

    public function __construct()
    {
        $this->departments = Cache::store('MonthlyDepartment')->getMonthlyDepartment();
    }

    public function getDepartment($id)
    {
        return Cache::store('MonthlyDepartment')->getMonthlyDepartment($id);
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
            Cache::store('MonthlyDepartment')->deleteAll();
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 获取部门下所有的用户信息
     * @param $department_id
     * @return array
     * @throws Exception
     */
    public function getDepartmentUser($department_id)
    {
        $departments = Cache::store('MonthlyDepartment')->tree();

        $departmentUserMapService = new DepartmentUserMapService();
        $userData = [];
        if (isset($departments[$department_id])) {
            $child_ids = $departments[$department_id]['child_ids'];
            if (!empty($child_ids)) {
                foreach ($child_ids as $k => $child) {
                    $user_ids = $departmentUserMapService->getUserByDepartmentId($child);
                    array_push($userData, $user_ids);
                }
            }else{
                $user_ids = $departmentUserMapService->getUserByDepartmentId($department_id);
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
     * 部门关联关系
     * @param string $job
     * @return array
     */
    public function nodes()
    {
        $ids = DepartmentModel::where('status',0)->column('id,pid,name');
        $arr = [];
        foreach ($ids as $v){
            $arr[$v['pid']][$v['id']] = ['id'=>$v['id'], 'name'=>$v['name']];
        }
        return $arr;
    }

    public function getAllDepartment()
    {
        return $this->tree(0,$this->nodes());
    }

    /**
     * @title 生成部门树状结构
     * @param int $top_ids
     * @param $nodes  eg:$this->>nodes()
     * @return array
     */
    public function tree($top_ids = 0, $nodes)
    {
        if(!is_array($top_ids)){
            if(is_string($top_ids)){
                $top_ids = explode(',',$top_ids);
            }else{
                $top_ids = [$top_ids];
            }
        }
        $tree = [];
        foreach ($top_ids as $pid){
            if(isset($nodes[$pid])){
                foreach ($nodes[$pid] as $k => $v){
                    $v['nodes'] = $this->tree($v['id'], $nodes);
                    $tree[$k] = $v;
                }
            }
        }
        return $tree;
    }

    public function getAllDepartmentTree($id = 0,$mode = null,$hasChil = false)
    {
        $tree = Cache::store('MonthlyDepartment')->tree();
        if(!is_null($mode)){
            $where[] = ['mode', '==',$mode];
            $tree = Cache::filter($tree, $where);
            if($hasChil){
                $tree['child_ids'] = $this->getDepartmentIdByPid(0,$mode);
            }
        }

        if($id){
            $tree = $tree[$id] ?? [];
        }
        return $tree;
    }


    public function getDepartmentIdByPid($pid=0,$mode = '')
    {
        $where = [
            'pid' => $pid,
        ];
        if($mode){
            $where['mode'] = $mode;
        }
        return (new DepartmentModel())->where($where)->column('id');
    }

    /**
     * 查最上级部门下的最最小组ID
     * @param int $mode
     * @return array
     */
    public function getDepartmentTree($mode =0)
    {
        $reData = [];
        $tree = $this->getAllDepartmentTree(0,$mode);

        $pid = $this->getDepartmentIdByPid(0,$mode);
        foreach ($pid as $id){
            $Ids = [];
            $this->junId($id,$tree,$Ids);
            $reData[$id] = $Ids;

        }
        return $reData;
    }

    /**
     * 循环查最小组ID
     * @param $id
     * @param $tree
     * @param $name
     * @return string
     */
    private function junId($id,$tree,&$Ids)
    {
        if(!isset($tree[$id]['is_bottom'])){
            return false;
        }
        if($tree[$id]['is_bottom'] == 1){
            return $Ids[] = $id;
        }
        if($tree[$id]['child_ids']){
            foreach ($tree[$id]['child_ids'] as $i){
                $this->junId($i,$tree,$Ids);
            }
        }
    }

    /**
     * 查找某个部门下的最上级部门名称
     * @param $departmentId
     * @param int $mode
     * @return string
     */
    public function getPidDepartmentName($departmentId,$mode =0)
    {
        $reData = '';
        $tree = $this->getAllDepartmentTree(0,$mode);
        $this->junName($departmentId,$tree,$reData);
        return $reData;
    }

    /**
     * 循环查最上级部门名称
     * @param $id
     * @param $tree
     * @param $name
     * @return string
     */
    private function junName($id,$tree,&$name)
    {
        if(!isset($tree[$id]['pid'])){
            return '';
        }
        if($tree[$id]['pid'] == 0){
            return $name = $tree[$id]['name'];
        }
        $this->junName($tree[$id]['pid'],$tree,$name);
    }

}