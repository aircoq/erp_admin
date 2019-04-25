<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\monthly\MonthlyTargetDepartment as MonthlyDepartmentModel;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap as MonthlyDepartmentUserMap;
use app\common\service\MonthlyModeConst;
use app\report\service\MonthlyTargetAmountService;
use app\report\service\MonthlyTargetDepartmentService;
use think\Exception;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class MonthlyDepartment extends Cache
{
    /** 获取所有部门信息
     * @param int $id
     * @return array|mixed
     */
    public function getMonthlyDepartment($id = 0)
    {
        if ($this->redis->exists('cache:MonthlyDepartment')) {
            $result = json_decode($this->redis->get('cache:MonthlyDepartment'), true);
            if ($id) {
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:MonthlyDepartment'), true);
        }


        $newResult = $this->getAllMonthlyTarget(true);

        $this->redis->set('cache:MonthlyDepartment', json_encode($newResult));
        if ($id) {
            return isset($newResult[$id]) ? $newResult[$id] : [];
        }
        return $newResult;
    }

    public function getMonthlyDepartmentOptions()
    {
        $departments = $this->getMonthlyDepartment();
        $result = [];
        foreach ($departments as $id => $department) {
            $result[$id] = $department['name'];
        }
        return $result;
    }

    public function getMonthlyDepartmentTree()
    {
        $departments = $this->tree();
        $result = [];
        foreach ($departments as $id => $department) {
            $result[$id] = $department['name_path'] ?? $department['name'] ?? '';
        }
        return $result;
    }

    /**
     * 删除
     */
    public function delete()
    {
        if ($this->redis->exists('cache:MonthlyDepartment')) {
            return $this->redis->del('cache:MonthlyDepartment');
        }
        return false;
    }

    public function deleteAll()
    {
        $this->delete();
        Cache::handler()->del('cache:monthly_department_tree');
        Cache::handler()->del('cache:MonthlyDepartmentLeader');
    }

    private function changeLeaderName(&$v)
    {
        $leader_name = [];
        if($v['leader_id']){
            $v['leader_id'] = json_decode($v['leader_id'],true);
            if(!is_array($v['leader_id'])){
                $v['leader_id'] = [$v['leader_id']];
            }
            foreach($v['leader_id'] as $l => $user_id){
                if($user_id){
                    $userInfo =  Cache::store('user')->getOneUser($user_id);
                    $user_name = $userInfo['realname'] ?? '';
                    $leader_name[] = $user_name;
                }
            }
        }
        $v['leader_name'] = $leader_name;
    }

    /** 获取分类树
     * @return array|mixed
     */
    public function tree()
    {
        $result = [];
        if ($this->redis->exists('cache:monthly_department_tree')) {
            $result = json_decode($this->redis->get('cache:monthly_department_tree'), true);
            return $result;
        } else {
            $departmentData = $this->getAllMonthlyTarget();
        }
        try {
            if ($departmentData) {
                $child = '_child';
                $child_ids = [];
                $temp = [
                    'depr' => '-',
                    'parents' => [],
                    'child_ids' => [],
                    'dir' => [],
                    '_child' => [],
                ];
                $func = function ($tree) use (&$func, &$result, &$temp, &$child, &$icon, &$child_ids) {
                    foreach ($tree as $k => $v) {
                        $v['parents'] = $temp['parents']; //所有父节点
                        $v['depth'] = count($temp['parents']); //深度
                        $v['name_path'] = empty($temp['name']) ? $v['name'] : implode($temp['depr'],
                                $temp['name']) . $temp['depr'] . $v['name']; //英文名路径
                        if (isset($v[$child])) {
                            $_tree = $v[$child];
                            unset($v[$child]);
                            $temp['parents'][] = $v['id'];
                            $temp['name'][] = $v['name'];
                            $result[$k] = $v;
                            if ($v['pid'] == 0) {
                                if (empty($child_ids)) {
                                    $child_ids = [$k];
                                } else {
                                    array_push($child_ids, $k);
                                }
                            }
                            $func($_tree);
                            foreach ($result as $value) {
                                if ($value['pid'] == $k) {
                                    $temp['child_ids'] = array_merge($temp['child_ids'], [$value['id']]);
                                }
                            }
                            $result[$k]['child_ids'] = $temp['child_ids']; //所有子节点
                            $temp['child_ids'] = [];
                            array_pop($temp['parents']);
                            array_pop($temp['name']);
                        } else {
                            $v['child_ids'] = [];
                            $result[$k] = $v;
                            if ($v['pid'] == 0) {
                                if (empty($child_ids)) {
                                    $child_ids = [$k];
                                } else {
                                    array_push($child_ids, $k);
                                }
                            }
                        }
                    }
                };
                $_list = [];
                foreach ($departmentData as $k => $v) {
                    $_list['model'][$v['id']] = $v;
                }
                foreach ($_list as $k => $v) {
                    $func(list_to_tree($v));
                }
            }
            $result['child_ids'] = $child_ids;
            //加入redis中
            self::set('monthly_department_tree', json_encode($result));
        } catch (Exception $e) {
            Cache::handler()->hSet('hash:department:tree:log' . ':' . date('Ymd') . ':' . date('H'), time() . '-' . date('Ymd H:i:s'),$e->getMessage());
        }
        return $result;
    }

    private function getAllMonthlyTarget($isIds = false)
    {
        $departmentModel = new MonthlyDepartmentModel();
        $department_list =$departmentModel->alias('a')->field('a.id,a.pid,a.name,a.type,a.status,a.is_bottom,a.leader_id,a.mode')
            ->order('sort desc,id ASC')
            ->select();


        $ids = [];
        foreach ($department_list as $k => $v) {
            $ids[] = $v['id'];
        }
        $target = (new MonthlyTargetAmountService())->getTarget($ids,1);
        $departmentData = [];
        foreach($department_list as $key => $value){
            $value = $value->toArray();
            $this->changeLeaderName($value);
            $value['target_amount'] = $target[$value['id']] ?? 0;
            $value['target_amount'] = str_replace('.00','',$value['target_amount']);
            if($isIds){
                $departmentData[$value['id']] = $value;
            }else{
                array_push($departmentData,$value);
            }
        }
        return $departmentData;
    }

    /** 获取所有部门信息负责人
     * @param int $id
     * @return array|mixed
     */
    public function getMonthlyDepartmentLeader($id = 0)
    {
        if ($this->redis->exists('cache:MonthlyDepartmentLeader')) {
            $result = json_decode($this->redis->get('cache:MonthlyDepartmentLeader'), true);
            if ($id) {
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:MonthlyDepartmentLeader'), true);
        }

        $newResult = [];
        $list = $this->getMonthlyDepartment();
        foreach ($list as $v){
            if($v['leader_id'] &&  $v['status'] == 0){
                foreach ($v['leader_id'] as $userid){
                    if($userid > 0){
                        $newResult[$userid] = $v['id'];
                    }
                }
            }
        }
        $this->redis->set('cache:MonthlyDepartmentLeader', json_encode($newResult));
        if ($id) {
            return isset($newResult[$id]) ? $newResult[$id] : [];
        }
        return $newResult;
    }

    public function getUserProgress($userId)
    {
        $key = 'cache:MonthlyDepartment:Progress:'.$userId;
        if($this->redis->exists($key)){
            $value = $this->redis->get($key);
            if(date('d') == $value){
                return true;
            }
        }
        return false;
    }

    public function setUserProgress($userId)
    {
        $key = 'cache:MonthlyDepartment:Progress:'.$userId;
        $this->redis->set($key, date('d'),86400);
        return false;
    }

}
