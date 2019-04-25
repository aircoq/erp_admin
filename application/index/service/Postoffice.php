<?php


namespace app\index\service;

use app\common\model\Postoffice as ModelPostoffice;
USE app\index\validate\Postoffice as ValidatePostoffice;
use think\Exception;

class Postoffice
{

    private function getWhere($param)
    {
        $o = new ModelPostoffice();
        $o = $o->join("(SELECT  post_id,count(*) as num FROM email where post_id > 0  GROUP BY post_id) as t ", "t.post_id=p.id", 'left');
        if (isset($param['status'])) {
            if ($param['status'] == '0') {
                $o = $o->where('p.status', 0);
            } else if ($param['status']) {
                $o = $o->where('p.status', $param['status']);
            }
        }
        if (isset($param['post']) && $param['post']) {
            $o = $o->where('p.post', 'like', $param['post'] . "%");
        }

        $is_null = false;
        if (isset($param['email_count_st']) && $param['email_count_st'] !== '') {
            $o = $o->where('t.num', '>=', intval($param['email_count_st']));
            if (!$param['email_count_st']) {
                $o = $o->whereOr('t.num', 'exp', 'is null');
                $is_null = true;
            }
        }
        if (isset($param['email_count_nd']) && $param['email_count_nd'] !== '') {
            $o = $o->where('t.num', '<=', intval($param['email_count_nd']));
            if (!$param['email_count_nd']) {
                if (!$is_null) {
                    $o = $o->whereOr('t.num', 'exp', 'is null');
                }
            }
        }
        if (isset($param['create_time_st']) && $param['create_time_st']) {
            $time = strtotime($param['create_time_st']);
            $o = $o->where('p.create_time', '>=', $time);
        }
        if (isset($param['create_time_nd']) && $param['create_time_nd']) {
            $time = strtotime($param['create_time_nd'] . " 23:59:59");
            $o = $o->where('p.create_time', '<=', $time);
        }
        $o = $o->alias('p');
        return $o;
    }

    private function row($v)
    {
        $row = [];
        isset($v['id']) && $row['id'] = $v['id'];
        isset($v['post']) && $row['post'] = $v['post'];
        isset($v['email_count']) && $row['email_count'] = (int)$v['email_count'];
        isset($v['imap_url']) && $row['imap_url'] = $v['imap_url'];
        isset($v['smtp_url']) && $row['smtp_url'] = $v['smtp_url'];
        if (isset($v['smtp_port'])) {
            $row['smtp_port'] = $v['smtp_port'] ? $v['smtp_port'] : '';
        }
        if (isset($v['imap_port'])) {
            $row['imap_port'] = $v['imap_port'] ? $v['imap_port'] : '';
        }
        isset($v['status']) && $row['status'] = $v['status'];
        if (isset($v['create_id'])) {
            $row['create_id'] = $v['creator_id'];
            $row['create_txt'] = $v['create_txt'];
        }
        if (isset($v['create_time'])) {
            $row['create_time'] = $v['create_time'];
            $row['create_time_date'] = $v['create_time_date'];
        }
        return $row;
    }

    private function lists($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $result[] = $this->row($v);
        }
        return $result;
    }

    public function index($page, $pageSize, $param)
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['pageSize'] = $pageSize;
        $result['count'] = $this->getWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getWhere($param);
        $ret = $o->page($page, $pageSize)
            ->field("p.id,p.post,t.num as email_count,p.imap_url,p.smtp_url,p.status,p.creator_id,p.create_time")
            ->order('p.id desc')->select();
        if ($ret) {
            $result['list'] = $this->lists($ret);
        }
        return $result;
    }

    public function read($id)
    {
        $o = new ModelPostoffice();
        $ret = $o->field('id,creator_id,create_time,post,imap_url,imap_port,smtp_url,smtp_port,status')
            ->where('id', $id)
            ->find();
        if (!$ret) {
            throw new Exception('该邮局信息不存在');
        }
        return $this->row($ret);
    }

    public function save($param, $user_id)
    {
        $param['creator_id'] = $user_id;
        $param['create_time'] = time();

        $validate = new ValidatePostoffice();
        $flag = $validate->scene('insert')->check($param);
        if ($flag === false) {
            throw new Exception($validate->getError());
        }
        try {
            $o = new ModelPostoffice();
            $o->allowField(true)->isUpdate(false)->save($param);
            return ['message' => '新增成功'];

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function update($id, $param, $user_id)
    {
        $param['updater_id'] = $user_id;
        $param['update_time'] = time();
        $o = new ModelPostoffice();
        $old = $o->field(true)->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该邮局信息不存在,无法修改');
        }
        if ($old['email_count'] > 0) {
            if (isset($param['post'])) {
                if ($param['post'] != $old['post']) {
                    throw new Exception('该邮局已绑定邮箱，不可修改');
                }
            }
        }
        if (empty($param['imap_url'])) {
            $param['imap_port'] = 0;
        }
        if (empty($param['smtp_url'])) {
            $param['smtp_port'] = 0;
        }
        $validate = new ValidatePostoffice();
        $flag = $validate->scene('update')->check($param);
        if ($flag === false) {
            throw new Exception($validate->getError());
        }
        try {
            $o = new ModelPostoffice();
            $o->allowField(true)->save($param, ['id' => $id]);
            return ['message' => '修改成功'];
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function changeStatus($id, $param, $user_id)
    {
        $param['updater_id'] = $user_id;
        $param['update_time'] = time();
        $o = new ModelPostoffice();
        $old = $o->field(true)->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该邮局信息不存在,无法修改');
        }
        $validate = new ValidatePostoffice();
        $flag = $validate->scene('update')->check($param);
        if ($flag === false) {
            throw new Exception($validate->getError());
        }
        try {
            $o = new ModelPostoffice();
            $o->allowField(true)->save($param, ['id' => $id, 'email_count' => 0]);
            return ['message' => '修改成功'];
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function getCanUsePost()
    {
        $o = new ModelPostoffice();
        $ret = $o->field('id,post')->where('status', 1)->select();
        foreach ($ret as &$v) {
            $v['post'] = '@' . $v['post'];
        }
        return $ret;
    }

    public function incCount($id, $inc = 1)
    {
        $Model = new ModelPostoffice();
        return $Model->where('id', $id)->setInc('email_count', $inc);
    }

    public function decCount($id, $inc = 1)
    {
        return ModelPostoffice::where('id', $id)->setDec('email_count', $inc);
    }

}