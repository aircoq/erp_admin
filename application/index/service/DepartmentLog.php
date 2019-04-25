<?php


namespace app\index\service;

use app\common\cache\Cache;
use app\common\cache\driver\Channel as driverChannel;
use app\common\model\DepartmentLog as Model;
use app\index\service\DepartmentTypeService;
use app\index\service\JobService;

class DepartmentLog extends BaseLog
{
    protected $fields = [
        'name' => '部门名称',
        'pid' => '上级部门',
        'type' => '类型',
        'status' => '状态',
        'remark' => '备注',
        'job' => '部门职能',
        'channel_id' => '所属平台',
        'leader_id' => '负责人',
        'leader_job' => '负责人职位',
    ];

    public function __construct()
    {
        $this->model = new Model();
    }

    protected $tableField = [
        'id' => 'department_id',
        'remark' => 'remark',
        'operator_id' => 'operator_id',
        'operator' => 'operator'
    ];

    public function add($name)
    {
        $list = [];
        $list['type'] = '部门信息';
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

    protected function mdfItem($name, $info)
    {
        $list = [];
        $list['type'] = $name;
        $list['val'] = '';
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;
    }

    protected function statusText($row)
    {
        $map = [
            '0' => '停用',
            '1' => '启用'
        ];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }

    protected function pidText($row)
    {
        $oldInfo = Cache::store('department')->getDetailById($row['old']);
        $newInfo = Cache::store('department')->getDetailById($row['new']);
        $old =  $oldInfo['name_path'];
        $new = $newInfo['name_path'];
        return "{$old} => {$new}";
    }

    protected function leader_idText($row)
    {
        $tempOld = [];
        foreach ($row['old'] as $v) {
            $name = Cache::store('user')->getOneUser($v['user_id']);
            $job = Cache::store('job')->getJob($v['job_id']);
            $jobName = $job['name'] ?? '';
            $tmp = $jobName ? $name['realname'] . "({$jobName})" : $name;
            $tempOld[] = $tmp;
        }
        $tmpNew = [];
        foreach ($row['new'] as $v) {
            $name = Cache::store('user')->getOneUser($v['user_id']);
            $job = Cache::store('job')->getJob($v['job_id']);
            $jobName = $job['name'] ?? '';
            $tmp = $jobName ? $name['realname'] . "({$jobName})" : $name;
            $tmpNew[] = $tmp;
        }

        $old = implode('、', $tempOld);
        $new = implode('、', $tmpNew);
        return "{$old} => {$new}";
    }

    protected function typeText($row)
    {
        $map = DepartmentTypeService::TYPE_TXT;
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }

    protected function channel_idText($row)
    {
        $driverChannel = new driverChannel();
        $oldChannelName = $driverChannel->getChannelTitle($row['old']);
        $newChannelName = $driverChannel->getChannelTitle($row['new']);
        return "{$oldChannelName} => {$newChannelName}";
    }

    protected function jobText($row)
    {
        $job = new JobService();
        $oldJobName = $job->getName($row['old']);
        $newJobName = $job->getName($row['new']);
        return "{$oldJobName} => {$newJobName}";
    }
}