<?php


namespace app\index\service;

use app\common\model\Phone as ModelPhone;
use app\common\service\Common;
use app\index\validate\Phone as ValidatePhone;
use think\Exception;
use app\common\cache\Cache;

class Phone
{
    private function getWhere($param)
    {

        if (isset($param['account_count_nd']) && $param['account_count_nd'] !== '') {
            if ($param['account_count_st'] === '') {
                $param['account_count_st'] = 0;
            }
        }
        $o = new ModelPhone();
        $o = $o->alias('p')->join(" (SELECT  phone_id,count(*) as num FROM account where phone_id > 0 GROUP BY phone_id) as t ", "p.id = t.phone_id", 'left');
        if (isset($param['status'])) {
            if ($param['status'] == '0') {
                $o = $o->where('status', 0);
            } else if ($param['status']) {
                $o = $o->where('p.status', $param['status']);
            }
        }
        if (isset($param['operator']) && $param['operator']) {
            $o = $o->where('p.operator', $param['operator']);
        }
        if (isset($param['phone']) && $param['phone']) {
            $o = $o->where('p.phone', 'like', "{$param['phone']}%");
        }
        $is_null = false;
        if (isset($param['account_count_st']) && $param['account_count_st'] !== '') {
            $o = $o->where('t.num', '>=', intval($param['account_count_st']));
            if (!$param['account_count_st']) {
                $o = $o->whereOr('t.num', 'exp', 'is null');
                $is_null = true;
            }
        }
        if (isset($param['account_count_nd']) && $param['account_count_nd'] !== '') {
            $o = $o->where('t.num', '<=', intval($param['account_count_nd']));
            if (!$param['account_count_nd']) {
                if (!$is_null) {
                    $o = $o->whereOr('t.num', 'exp', 'is null');
                }
            }
        }

        if (isset($param['reg_time_st']) && $param['reg_time_st']) {
            $time = strtotime($param['reg_time_st']);
            $o = $o->where('p.reg_time', '>=', $time);
        }
        if (isset($param['reg_time_nd']) && $param['reg_time_nd']) {
            $time = strtotime($param['reg_time_nd'] . " 23:59:59");
            $o = $o->where('p.reg_time', '<=', $time);
        }
        return $o;
    }

    public function getList($page, $page_size, $param)
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['pageSize'] = $page_size;
        $result['count'] = $this->getWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getWhere($param);
        $ret = $o->page($page, $page_size)
            ->field("p.id,p.phone,p.operator,p.status,p.reg_id,p.reg_time,t.num as account_count")
            ->order('p.id desc')->select();
        if ($ret) {
            $result['list'] = $this->fillData($ret);
        }
        return $result;
    }

    private function fillData($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $row = $this->analysisRow($v);
            $result[] = $row;
        }
        return $result;
    }

    private function analysisRow($v)
    {
        $row = [];
        isset($v['id']) && $row['id'] = $v['id'];
        isset($v['phone']) && $row['phone'] = $v['phone'];
        if (isset($v['operator'])) {
            $row['operator'] = $v->operator;
            $row['operator_txt'] = $v->operator_txt;
        }
        if (isset($v['account_count'])) {
            $row['account_count'] = (int)$v['account_count'];
        }
        isset($v['status']) && $row['status'] = $v['status'];
        if (isset($v['reg_id'])) {
            $row['reg_txt'] = $v->reg_txt;
            $row['reg_id'] = $v->reg_id;
        }
        if (isset($v['reg_time'])) {
            $row['reg_time_txt'] = $v->reg_time_txt;
            $row['reg_time'] = $v->reg_time;
        }
        return $row;

    }

    public function read($id)
    {
        $model = new ModelPhone();
        $ret = $model->where('id', $id)
            ->field('id,phone,operator,status,reg_id,reg_time,account_count')
            ->find();
        if (!$ret) {
            throw new Exception('当前信息不存在');
        }
        return $this->analysisRow($ret);
    }

    public function save($param)
    {
        $userInfo = Common::getUserInfo();
        $Model = new ModelPhone();
        $Validate = new ValidatePhone();
        if (isset($param['reg_time'])) {
            $param['reg_time'] = strtotime($param['reg_time']);
        }
        if (!empty($param['id'])) {
            $old = $Model->where('id', $param['id'])->find();
            if (!$old) {
                throw new Exception('当前记录不存在无法修改！');
            }
            $flag = $Validate->scene('update')->check($param);
            if ($flag === false) {
                throw new Exception($Validate->getError());
            }
            unset($param['id']);
            $old->allowField(true)->save($param);
            return ['message' => '修改成功!'];
        } else {
            $param['creator_id'] = $userInfo['user_id'];
            $param['create_time'] = time();
            $flag = $Validate->scene('insert')->check($param);
            if ($flag === false) {
                throw new Exception($Validate->getError());
            }
            $Model->allowField(true)->isUpdate(false)->save($param);
            return ['message' => '新增成功!'];
        }

    }

    public function changeStatus($id, $status)
    {
        $model = new ModelPhone();
        $old = $model->where('id', $id)
            ->find();
        if (!$old) {
            throw new Exception('当前信息不存在');
        }
        $Validate = new ValidatePhone();
        $param = ['status' => $status];
        $flag = $Validate->scene('update')->check($param);
        if ($flag === false) {
            throw new Exception($Validate->getError());
        }
        $old->allowField(true)->save($param);
        return ['message' => '修改成功!'];
    }

    private function getCanUsePhoneListWhere($param)
    {
        $o = new ModelPhone();
        $o = $o->where('status', ModelPhone::STATUS_ENABLE);
        if (isset($param['phone']) && $param['phone']) {
            $o = $o->where('phone', 'like', $param['phone'] . "%");
        }
        if (isset($param['channel_id']) && $param['channel_id']) {
            $o->where('id', 'exp', 'not in ( select phone_id from account where status != 6 and phone_id>0 and channel_id= ' . $param['channel_id'] . ' UNION ALL select phone_id from account_apply   where  phone_id>0 and status not in (4,5,6) and channel_id = ' . $param['channel_id'] . ' )  ');
        }
        return $o;
    }

    public function getCanUsePhoneList($page, $pageSize, $param)
    {

        $result = ['list' => []];
        $result['page'] = $page;
        $result['pageSize'] = $pageSize;
        $result['count'] = $this->getCanUsePhoneListWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getCanUsePhoneListWhere($param);
        $ret = $o->page($page, $pageSize)
            ->field("id,phone,operator,reg_id,reg_time")
            ->order('id desc')->select();
        if ($ret) {
            $result['list'] = $this->fillData($ret);
        }
        return $result;
    }

    private function getCanUserEmailPhoneWhere($param)
    {
        $o = new ModelPhone();
        $o = $o->where('status', ModelPhone::STATUS_ENABLE);
        if (isset($param['phone']) && $param['phone']) {
            $o = $o->where('phone', 'like', $param['phone'] . "%");
        }
        return $o;
    }


    public function getCanUserEmailPhone($page, $pageSize, $param)
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['pageSize'] = $pageSize;
        $result['count'] = $this->getCanUserEmailPhoneWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getCanUserEmailPhoneWhere($param);
        $ret = $o->page($page, $pageSize)
            ->field("id,phone,operator,reg_id,reg_time")
            ->order('id desc')->select();
        if ($ret) {
            $result['list'] = $this->fillData($ret);
        }
        return $result;
    }

    public function bind($id)
    {
        $Model = new ModelPhone();
        return $Model->where('id', $id)
            ->where('status', 1)
            ->setInc('account_count');
    }

    public function unbind($id)
    {
        $Model = new ModelPhone();
        return $Model->where('id', $id)
            ->setDec('account_count');
    }

    public function accounts($id)
    {
        $BasicAccount = new BasicAccountService();
        $ret = $BasicAccount->getAccountByPhoneId($id);
        $result = [];
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v->id;
            $row['account_code'] = $v->account_code;
            $row['site_code'] = $v->site_code;
            $row['company'] = $v->company_name;
            $row['channel_name'] = Cache::store('channel')->getChannelName($v['channel_id']);
            $result[] = $row;
        }
        return $result;
    }

    public function checkPhone($phone_id)
    {
        $model = new ModelPhone();
        $old = $model->where('id', $phone_id)
            ->find();
        if (!$old) {
            throw new Exception('当前手机号不存在，无法绑定');
        }
        if ($old['status'] == 0) {
            throw new Exception('当前手机号不可用，无法绑定');
        }
    }

}