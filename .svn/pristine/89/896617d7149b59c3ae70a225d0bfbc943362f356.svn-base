<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 17:27
 */

namespace app\index\service;

use think\Db;
use think\Request;
use app\common\model\User;
use app\common\cache\Cache;
use app\common\service\Encryption;
use app\common\exception\JsonErrorException;
use app\common\model\account\PayoneerAccount;
use app\common\service\Common;

class PayoneerService
{
    protected $model;
    /**
     * @var \app\common\cache\driver\User
     */
    protected $cache;

    public function __construct()
    {
        if (is_null($this->model)) {
            $this->model = new PayoneerAccount();
        }
        if (is_null($this->cache)) {
            $this->cache = Cache::store('user');
        }

    }

    /**
     * 接收错误并返回,当你调用此类时，如果遇到需要获取错误信息时，请使用此方法。
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function getPayoneerList()
    {
        $request = Request::instance();
        $params = $request->param();

        $order = 'payoneer_account.id';
        $sort = 'desc';
        $sortArr = [
            'account_name' => 'payoneer_account.account_name',
        ];
        if (!empty($params['order_by']) && !empty($sortArr[$params['order_by']])) {
            $order = $sortArr[$params['order_by']];
        }
        if (!empty($params['sort']) && in_array($params['sort'], ['asc', 'desc'])) {
            $sort = $params['sort'];
        }

        $where = $this->getWhere($params);
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $field = 'id,account_name,belong,phone,company_name,registered_name,client_code,birthday,status,operator_id,create_id,create_time';

        $count = $this->model
            ->where($where)
            ->count();
        $list = $this->model
            ->field($field)
            ->where($where)
            ->order($order, $sort)
            ->page($page, $pageSize)
            ->select();

        foreach ($list as $key => $item) {
            $list[$key]['create'] = $this->cache->getOneUserRealname($item['create_id']);
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $list[$key]['birthday'] = $item['birthday'] ? date('Y-m-d', $item['birthday']) : '';
            $list[$key]['operator'] = $this->cache->getOneUserRealname($item['operator_id']);
        }
        $result = [
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;

    }

    /**
     * 根据ID查询记录
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $info = $this->model->where(['id' => $id])->find();
        if (!$info) {
            $this->error = '查询无记录';
            return false;
        }

        $info['operator'] = $this->cache->getOneUserRealname($info['operator_id']);
        $info['create'] = $this->cache->getOneUserRealname($info['create_id']);
        $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        $info['birthday'] = $info['birthday'] ? date('Y-m-d', $info['birthday']) : '';
        return $info;
    }

    /**
     * 保存记录信息
     * @param $data
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\exception\DbException
     */
    public function save($data)
    {
        if ($this->model->where(['account_name' => $data['account_name']])->value('account_name')) {
            $this->error = '该账号已存在';
            return false;
        }

        try {
            Db::startTrans();
            $Encryption = new Encryption();
            if (isset($data['email_password'])) {
                $data['email_password'] = $Encryption->encrypt($data['email_password']);
            }

            if (isset($data['encrypted_data'])) {
                $data['encrypted_data'] = $Encryption->encrypt($data['encrypted_data']);
            }

            $this->model->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $this->model->id;
            Db::commit();
        } catch (JsonErrorException $e) {
            $this->error = $e->getMessage();
            Db::rollback();
            return false;
        }

        $info = $this->model->field(true)->where(['id' => $new_id])->find();
        $info['operator'] = $this->cache->getOneUserRealname($info['operator_id']);
        $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        return $info;
    }

    /**
     * 更新记录
     * @param $id
     * @param $data
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\exception\DbException
     */
    public function update($id, $data)
    {
        if (!$find = $this->read($id)) {
            return false;
        }

        try {
            Db::startTrans();
            unset($data['id']);
            $Encryption = new Encryption();
            if (isset($data['email_password']) && $find->email_password !== $data['email_password']) {
                $data['email_password'] = $Encryption->encrypt($data['email_password']);
            }

            if (isset($data['encrypted_data']) && $find->email_password !== $data['encrypted_data']) {
                $data['encrypted_data'] = $Encryption->encrypt($data['encrypted_data']);
            }

            $this->model->allowField(true)->save($data, ['id' => $id]);
            Db::commit();
        } catch (JsonErrorException $e) {
            $this->error = $e->getMessage() . $e->getFile() . $e->getLine();
            Db::rollback();
            return false;
        }

        $info = $this->model->field(true)->where(['id' => $id])->find();
        $info['operator'] = $this->cache->getOneUserRealname($info['operator_id']);
        $info['create'] = $this->cache->getOneUserRealname($info['create_id']);
        $info['birthday'] = date('Y-m-d', $info['birthday']);
        return $info;
    }

    /**
     * 展示密码
     * @param $password
     * @param $id
     * @param $type
     * @return bool|string
     * @throws \think\exception\DbException
     */
    public function viewPassword($password, $id, $type)
    {
        $user = Common::getUserInfo();
        if (empty($user)) {
            $this->error = '非法操作';
            return false;
        }
        $userFind = (new User())->where(['id' => $user['user_id']])->find();
        if (empty($userFind)) {
            $this->error = '外来物种入侵';
            return false;
        }
        if ($userFind['password'] != User::getHashPassword($password, $userFind['salt'])) {
            $this->error = '登录密码错误';
            return false;
        }

        $discount = $this->read($id);
        if (!$discount) {
            return false;
        }

        $encryption = new Encryption();
        $enablePassword = '';
        switch ($type) {
            case 'email_password':
                $enablePassword = $encryption->decrypt($discount['email_password']);
                break;
            case 'encrypted_data':
                $enablePassword = $encryption->decrypt($discount['encrypted_data']);
                break;
        }
        return $enablePassword;
    }

    /**
     * 查询条件获取
     * @param $params
     * @return array
     */
    public function getWhere($params)
    {
        $where = [];
        if (isset($params['status']) && ($params['status'] !== '')) {
            $where['payoneer_account.status'] = ['eq', $params['status']];
        }

        if (isset($params['operator_id']) && ($params['operator_id'] !== '')) {
            $where['payoneer_account.operator_id'] = ['eq', $params['operator_id']];
        }

        if (!empty($params['snText'])) {
            $snText = trim($params['snText']);
            $snText = is_json($snText) ? json_decode($snText) : [$snText];
            switch ($params['snType']) {
                case 'account_name':
                    $where['payoneer_account.account_name'] = ['in', $snText];
                    break;
                case 'company_name':
                    $where['payoneer_account.company_name'] = ['in', $snText];
                    break;
                default:
                    break;
            }
        }
        return $where;
    }


    /**
     * 编辑
     * @param $id
     * @return bool|string
     * @throws \think\exception\DbException
     */
    public function editStatus($id, $status)
    {
        $result = $this->read($id);

        if (!$result) {
            return false;
        }
        $data['status'] = 0;
        if ($status == 1) {
            $data['status'] = 1;
        }
        return $this->model->edit($data, ['id' => $id]);
    }

}