<?php


namespace app\index\service;

use think\Exception;

abstract class BaseLog
{
    protected $fields = [];
    protected $limit_size = 10;
    protected $LogData = [];
    protected $tableField = [];
    protected $model = null;

    protected function getText()
    {
        $ret = [];
        foreach ($this->LogData as $list) {
            $total = count($list['data']);
            if ($total > $this->limit_size) {
                $page_size = $this->limit_size;
                $total_page = ceil($total / $page_size);
                for ($page = 1; $page < $total_page; $page++) {
                    $offset = ($page - 1) * $page_size;
                    $tmp1 = $list;
                    $tmp1['data'] = array_slice($list['data'], $offset, $page_size);
                    $ret[] = $tmp1;
                }
            } else {
                $ret[] = $list;
            }
        }
        $tmp = [];
        foreach ($ret as $list) {
            $result = '';
            if ($list['exec'] == 'mdf') {
                if (!$list['data']) {
                    continue;
                }
                $exec = '修改';
                $result .= $exec . '【' . $list['type'] . '】';
                $arr_temp = [];
                foreach ($list['data'] as $key => $row) {
                    $str = '';
                    $strFun = $key . "Text";
                    $keyName = $this->fields[$key];
                    $str .= $keyName . ":";
                    if (in_array($strFun, get_class_methods(static::class))) {
                        $str .= $this->$strFun($row);
                    } else {
                        $str .= $this->otherText($row);
                    }
                    $arr_temp[] = $str;
                }
                $result .= implode(";", $arr_temp);
            } else if ($list['exec'] == 'add') {
                $exec = '新增';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else if ($list['exec'] == 'del') {
                $exec = '删除';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else if ($list['exec'] == 'create') {
                $exec = '创建';
                $result .= $exec . $list['type'] . "{$list['val']}";
            } else if ($list['exec'] == 'agree') {
                $exec = $list['type'].'审批通过' . $list['val'];
                $result .= $exec;
            } else if ($list['exec'] == 'disagree') {
                $exec = $list['type'].'审批不通过,' . $list['val'];
                $result .= $exec;
            } else if ($list['exec'] == 'submit_audit') {
                $exec = '提交审批';
                $result .= $exec;
            } elseif ($list['exec'] == 'submit') {
                $exec = '提交' . $list['type'];
                $result .= $exec . "," . $list['val'];
            } else if ($list['exec'] == 'save') {
                $exec = '保存';
                if($list['val']){
                    $list['val'] = ",备注:" . $list['val'];
                }
                $result .= $exec . $list['type'] . $list['val'];
            } elseif ($list['exec'] == 'invalid') {
                $exec = '作废';
                if($list['val']){
                    $list['val'] = ",备注:" . $list['val'];
                }
                $result .= $exec . $list['type'] . $list['val'];
            }else{
                $result .= $list['exec'] . $list['type'].$list['val'];
            }
            $tmp[] = $result;
        }

        return $tmp;
    }


    protected function otherText($row)
    {
        return "{$row['old']} => {$row['new']}";
    }

    public function save($id = 0, $user_id, $realName, $resource = '')
    {
        $texts = $this->getText();
        if ($texts) {
            foreach ($texts as $text) {
                $o =clone $this->model;
                $data = [];
                $data[$this->tableField['id']] = $id;
                $data[$this->tableField['remark']] = $resource . $text;
                $data[$this->tableField['operator_id']] = $user_id;
                $data[$this->tableField['operator']] = $realName;
                $data['create_time'] = time();
                $o->allowField(true)->isUpdate(false)->save($data);
            }
        }
        $this->LogData = [];
    }

    public function getLog($id)
    {
        $user = new User();
        $result = $this->model->where($this->tableField['id'], $id)->order('id desc')->select();
        foreach ($result as &$v){
            $v['department_name'] = $user->getUserDepartmentName($v['operator_id']);
        }
        return $result;

    }
}