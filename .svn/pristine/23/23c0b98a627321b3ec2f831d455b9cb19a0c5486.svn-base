<?php


namespace app\goods\service;


use think\Exception;
use app\common\validate\GoodsDeveloper as ValidateGoodsDeveloper;
use app\common\model\GoodsDeveloper as ModelGoodsDeveloper;

class GoodsDeveloper
{
    public function addDeveloper($data, $user_id)
    {
        $time = time();
        $result = [];
        foreach ($data as $row) {
            $row['create_time'] = $time;
            $row['create_id'] = $user_id;
            $tmp = $row['translator'];
            $tmp1 = [];
            foreach ($tmp as $v) {
                $_row = [];
                $_row['lang'] = $v['lang'];
                $_row['translator'] = $v['translator'];
                $tmp1[$_row['lang']] = $_row;
            }
            $row['translator'] = json_encode($tmp1);
            $ValidateGoodsDeveloper = new ValidateGoodsDeveloper();
            $flag = $ValidateGoodsDeveloper->scene('insert')->check($row);
            $model = new ModelGoodsDeveloper();
            try {
                if ($flag === false) {
                    throw new Exception($ValidateGoodsDeveloper->getError());
                }
                $count = $model->where('developer_id', $row['developer_id'])->count();
                if ($count) {
                    $user_name = $model->getDeveloperAttr(null, ['developer_id' => $row['developer_id']]);
                    throw new Exception("开发员【{$user_name}】已存在");
                }
                $model = new ModelGoodsDeveloper();
                $model->allowField(true)->isUpdate(false)->save($row);
                $msg = ['status' => 0, 'message' => '开发员' . $model->developer . "添加成功!"];
                $result[] = $msg;
            } catch (Exception $ex) {
                $result[] = ['status' => 1, 'message' => $ex->getMessage()];
            }
        }
        return $result;
    }

    public function developerUpdate($param, $id)
    {
        $data = [];
        isset($param['grapher']) && $data['grapher'] = $param['grapher'];
        if (isset($param['translator'])) {
            $tmp = json_decode($param['translator'], true);
            $tmp1 = [];
            foreach ($tmp as $v) {
                $_row = [];
                $_row['lang'] = $v['lang'];
                $_row['translator'] = $v['translator'];
                $tmp1[$_row['lang']] = $_row;
            }
            $data['translator'] = json_encode($tmp1);
        }
        isset($param['designer_master']) && $data['designer_master'] = $param['designer_master'];
        if ($data) {
            $model = new ModelGoodsDeveloper();
            $old = $model->where('developer_id', $id)->find();
            if (!$old) {
                throw new Exception('该开发员不存在');
            }
            $ValidateGoodsDeveloper = new ValidateGoodsDeveloper();
            $flag = $ValidateGoodsDeveloper->scene('edit')->check($data);
            if ($flag === false) {
                throw new Exception($ValidateGoodsDeveloper->getError());
            }
            $model->allowField(true)->isUpdate(true)->save($data, ['developer_id' => $id]);
            return ['message' => '更改成功'];
        }
        throw new Exception('提交数据为空');
    }

    public function developerWhere($param)
    {
        $o = new ModelGoodsDeveloper();
        if (isset($param['developer_id']) && $param['developer_id']) {
            $o = $o->where('developer_id', $param['developer_id']);
        }
        if (isset($param['grapher']) && $param['grapher']) {
            $o = $o->where('grapher', $param['grapher']);
        }
        if (isset($param['translator']) && $param['translator']) {
            $o = $o->where(" JSON_CONTAINS(translator->'$.*.translator','{$param['translator']}','$')", '<>', '');
        }
        if (isset($param['designer_master']) && $param['designer_master']) {
            $o = $o->where('designer_master', $param['designer_master']);
        }
        if (isset($param['create_time_st']) && $param['create_time_st']) {
            $time = strtotime($param['create_time_st']);
            $o = $o->where('create_time', '>=', $time);
        }
        if (isset($param['create_time_nd']) && $param['create_time_nd']) {
            $time = strtotime($param['create_time_nd'] . " 23:59:59");
            $o = $o->where('create_time', '<=', $time);
        }
        return $o;
    }

    public function developer($param, $page, $page_size)
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['page_size'] = $page_size;
        $result['count'] = $this->developerWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $ret = $this->developerWhere($param)->page($page, $page_size)->order('create_time', 'desc')->select();
        $result['list'] = $this->filldeveloperData($ret);
        return $result;
    }

    private function filldeveloperData($ret)
    {
        $result = [];
        foreach ($ret as $list) {
            $row = [];
            $row['developer'] = $list->developer;
            $row['developer_id'] = $list->developer_id;
            $row['grapher'] = $list->grapher;
            $row['grapher_txt'] = $list->grapher_txt;
            $row['translator_txt'] = $list->translator_txt;
            $row['designer_master'] = $list->designer_master;
            $row['designer_master_txt'] = $list->designer_master_txt;
            $row['create_time'] = $list->create_time;
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 返回开发员信息
     * @author starzhan <397041849@qq.com>
     */
    public function getDeveloperInfo($developer_id)
    {
        $model = new ModelGoodsDeveloper();
        return $model->where('developer_id', $developer_id)->find();
    }

    public function getDeveloperById($developer_id)
    {
        $ret = $this->getDeveloperInfo($developer_id);
        if (!$ret) {
            throw new Exception('该信息不存在!');
        }
        $tmp = json_decode($ret['translator'], true);
        $result = [];
        foreach ($tmp as $v) {
            $result[] = $v;
        }
        $ret['translator'] = $result;
        return $ret;
    }

    public function removeDeveloper($id)
    {
        $model = new ModelGoodsDeveloper();
        $model->where('developer_id', $id)->delete();
        return ['message' => '删除成功!'];
    }
}