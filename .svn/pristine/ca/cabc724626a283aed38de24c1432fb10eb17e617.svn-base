<?php


namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\UserLog as Model;

class UserLog extends BaseLog
{
    protected $fields = [
        'username' => '用户名',
        'job' => '职务',
        'password' => '密码',
        'department_id' => '部门/职位',
        'email' => '邮箱',
        'realname' => '姓名',
        'mobile' => '手机号',
        'job_number' => '工号',
        'status' => '状态',
        'role_id' => '角色',
        'on_job'=>'在职状态'
    ];

    public function __construct()
    {
        $this->model = new Model();
    }

    protected $tableField = [
        'id' => 'user_id',
        'remark' => 'remark',
        'operator_id' => 'operator_id',
        'operator' => 'operator'
    ];

    public function add($name)
    {
        $list = [];
        $list['type'] = '用户';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdf($name, $old, $new)
    {
        $data = $this->mdfData($old, $new);
        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfItem($name, $info);
        return $this;
    }

    protected function mdfData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys($this->fields))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    protected function role_idText($row)
    {
        $oldName = [];
        foreach ($row['old'] as $oldId) {
            $oldInfo = Cache::store('role')->getRole($oldId);
            $oldName[] = $oldInfo['name']??'';
        }
        $newName = [];
        foreach ($row['new'] as $oldId) {
            $newInfo = Cache::store('role')->getRole($oldId);
            $newName[] = $newInfo['name']??'';
        }
        return implode('、', $oldName) . ' => ' . implode('、', $newName);
    }

    public function jobText($row)
    {
        $jobService = new JobService();
        $old = $jobService->getName($row['old']);
        $new = $jobService->getName($row['new']);
        return "{$old} => {$new}";
    }

    protected function department_idText($row)
    {
        $tempOld = [];
        foreach ($row['old'] as $v) {
            $name = Cache::store('department')->getDepartment($v['id']);
            $job = Cache::store('job')->getJob($v['job_id']);
            $jobName = $job['name'] ?? '';
            $depart_name = $name['name']??'无';
            $tmp = $jobName ? $depart_name . "({$jobName})" : $depart_name;
            $tempOld[] = $tmp;
        }
        $tmpNew = [];
        foreach ($row['new'] as $v) {
            $name = Cache::store('department')->getDepartment($v['id']);
            $job = Cache::store('job')->getJob($v['job_id']);
            $jobName = $job['name'] ?? '';
            $depart_name = $name['name']??'无';
            $tmp = $jobName ? $depart_name . "({$jobName})" : $depart_name;
            $tmpNew[] = $tmp;
        }
        $old = implode('、', $tempOld);
        $new = implode('、', $tmpNew);
        return "{$old} => {$new}";
    }

    protected function statusText($row)
    {
        $map = ['0'=>'禁用','1'=>'启用'];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }
    protected function on_jobText($row)
    {
        $map = ['0'=>'离职','1'=>'在职'];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }


    protected function mdfItem($name, $info)
    {
        $list = [];
        $list['type'] = $name;
        $list['val'] = '';
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;
    }

}