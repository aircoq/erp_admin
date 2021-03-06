<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Department as DepartmentModel;
use app\common\model\DepartmentUserMap;
use think\Exception;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Department extends Cache
{
    /** 获取所有部门信息
     * @param int $id
     * @return array|mixed
     */
    public function getDepartment($id = 0)
    {
        if ($this->redis->exists('cache:Department')) {
            $result = json_decode($this->redis->get('cache:Department'), true);
            if ($id) {
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:Department'), true);
        }
        $departmentModel = new DepartmentModel();
        $departmentUserMapModel = new DepartmentUserMap();
        //查表
        $result = $departmentModel->field('id, name, pid, status, remark,job,type,channel_id')->select();
        $newResult = [];
        foreach ($result as $k => $v) {
            $v = $v->toArray();
            $leaderList = $departmentUserMapModel->field('user_id')->where(['department_id' => $v['id'], 'is_leader' => 1])->select();
            $leader_id = [];
            foreach ($leaderList as $l => $user) {
                array_push($leader_id, $user['user_id']);
            }
            $v['leader_id'] = $leader_id;
            $newResult[$v['id']] = $v;
        }
        $this->redis->set('cache:Department', json_encode($newResult));
        if ($id) {
            return isset($newResult[$id]) ? $newResult[$id] : [];
        }
        return $newResult;
    }

    public function getDepartmentOptions()
    {
        $departments = $this->getDepartment();
        $result = [];
        foreach ($departments as $id => $department) {
            $result[$id] = $department['name'];
        }
        return $result;
    }

    /**
     * 删除
     */
    public function delete()
    {
        // add by 2018-04-08
        $this->redis->exists('cache:department_tree') && $this->redis->del('cache:department_tree');

        if ($this->redis->exists('cache:Department')) {
            return $this->redis->del('cache:Department');
        }
        return false;
    }

    /** 获取分类树
     * @return array|mixed
     */
    public function tree()
    {
        $result = [];
        if ($this->redis->exists('cache:department_tree')) {
            $result = json_decode($this->redis->get('cache:department_tree'), true);
            return $result;
        } else {
            $departmentModel = new DepartmentModel();
            $department_list = $departmentModel->master()->alias('a')->field('a.id,a.pid,a.name,a.job,a.type,a.status,a.remark,a.channel_id')
                ->where('a.delete_time', null)
                ->order('id ASC')
                ->select();
            $departmentData = [];
            $departmentUserMapModel = new DepartmentUserMap();
            foreach ($department_list as $key => $value) {
                $value = $value->toArray();
                $leaderList = $departmentUserMapModel->master()->field('user_id,job_id')->where(['department_id' => $value['id'], 'is_leader' => 1])->select();
                $leader_id = [];
                $leader_name = [];
                foreach ($leaderList as $l => $user) {
                    $userInfo = Cache::store('user')->getOneUser($user['user_id']);
                    $user_name = $userInfo['realname'] ?? '';
                    $info['name'] = $user_name;
                    $info['job'] = Cache::store('job')->getJob($user['job_id'])['name'] ?? '';
                    array_push($leader_name, $info);
                    array_push($leader_id, $user['user_id']);
                }
                $value['leader_id'] = $leader_id;
                $value['leader_name'] = $leader_name;
                array_push($departmentData, $value);
            }
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
            $this->redis->set('cache:department_tree', json_encode($result));
        } catch (Exception $e) {
            $this->redis->hSet('hash:department:tree:log' . ':' . date('Ymd') . ':' . date('H'), time() . '-' . date('Ymd H:i:s'), $e->getMessage());
        }
        return $result;
    }

    public function getDetailById($id)
    {
        $tree = $this->tree();
        return $tree[$id] ?? [];
    }


}
