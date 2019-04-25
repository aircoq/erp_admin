<?php


namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\user\RoleLog as Model;

class RoleLog extends BaseLog
{
    protected $fields = [
        'name' => '用户组名称',
        'sales_company_id' => '运营公司id',
        'sort' => '排序',
        'status' => '状态',
        'type' => '类型',
        'remark' => '备注',
        'delete_time' => '删除时间',

    ];

    public function __construct()
    {
        $this->model = new Model();
    }

    protected $tableField = [
        'id' => 'role_id',
        'remark' => 'remark',
        'operator_id' => 'operator_id',
        'operator' => 'operator'
    ];

    public function add($name)
    {
        $list = [];
        $list['type'] = '角色';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function addMember($val)
    {
        $list = [];
        $list['type'] = '【成员】>>';
        $list['val'] = $val;
        $list['data'] = [];
        $list['exec'] = '成员变更';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdfRole($val)
    {
        $list = [];
        $list['type'] = '【权限】>>';
        $list['val'] = $val;
        $list['data'] = [];
        $list['exec'] = '权限变更';
        $this->LogData[] = $list;
        return $this;
    }

    public function del($name)
    {
        $list = [];
        $list['type'] = '角色';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'del';
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




    /**
     * 修改status状态(0,1)
     * @param $row
     * @return string
     */
    protected function statusText($row)
    {
        $map = ['0'=>'禁用','1'=>'启用'];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }

    protected function nameText($row)
    {
        return "{$row['old']} => {$row['new']}";
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