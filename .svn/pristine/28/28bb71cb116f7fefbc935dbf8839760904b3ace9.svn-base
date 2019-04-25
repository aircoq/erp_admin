<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 15:30
 */

namespace app\index\service;

use think\Db;
use think\Request;
use app\common\cache\Cache;
use app\common\model\User;
use app\common\service\Encryption;
use app\common\model\account\WorldfirstAccount;
use app\common\exception\JsonErrorException;
use app\common\service\Common as CommonService;

class WorldfirstService
{

    protected $model;
    /**
     * @var \app\common\cache\driver\User
     */
    protected $cache;

    public function __construct()
    {
        if (is_null($this->model)) {
            $this->model = new WorldfirstAccount();
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
    public function getWorldfirstList()
    {
        $request = Request::instance();
        $params = $request->param();

        $order = 'a.id';
        $sort = 'desc';
        $sortArr = [
            'wf_account' => 'a.wf_account',
            'operator_id' => 'a.operator_id',
            'status' => 'a.status',
            'create_time' => 'a.create_time',
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
        $field = 'a.id,a.server_id,a.wf_account,a.belong,a.operator_id,a.status,a.create_id,a.create_time,
        b.name as ip_name,b.ip as ip_address';

        $count = $this->model
            ->alias('a')
            ->join('server b', 'a.server_id=b.id', 'LEFT')
            ->where($where)
            ->count();
        $list = $this->model
            ->alias('a')
            ->join('server b', 'a.server_id=b.id', 'LEFT')
            ->field($field)
            ->where($where)
            ->order($order, $sort)
            ->page($page, $pageSize)
            ->select();

        foreach ($list as $key => $item) {
            $list[$key]['operator'] = $this->cache->getOneUserRealname($item['operator_id']);
            $list[$key]['create'] = $this->cache->getOneUserRealname($item['create_id']);
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
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
        $info = $this->model
            ->alias('a')
            ->join('server b', 'a.server_id=b.id', 'LEFT')
            ->field('a.*,b.name as ip_name,b.ip as ip_address')
            ->where(['a.id' => $id])->find();
        if (!$info) {
            $this->error = '查询无记录';
            return false;
        }
        $info['operator'] = $this->cache->getOneUserRealname($info['operator_id']);
        $info['create'] = $this->cache->getOneUserRealname($info['create_id']);
        $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        $info['update_time'] = date('Y-m-d H:i:s', $info['update_time']);
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
        if ($this->model->where(['wf_account' => $data['wf_account']])->value('wf_account')) {
            $this->error = '该账号已存在';
            return false;
        }

        try {
            Db::startTrans();
            $Encryption = new Encryption();
            if (isset($data['wf_password'])) {
                $data['wf_password'] = $Encryption->encrypt($data['wf_password']);
            }

            if ($data['encrypted_answers']) {
                $data['encrypted_answers'] = $Encryption->encrypt($data['encrypted_answers']);
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
            if (isset($data['wf_password']) && $data['wf_password'] !== $find->wf_password) {
                $data['wf_password'] = $Encryption->encrypt($data['wf_password']);
            }

            if (isset($data['encrypted_answers']) && $data['encrypted_answers'] !== $find->encrypted_answers) {
                $data['encrypted_answers'] = $Encryption->encrypt($data['encrypted_answers']);
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
        return $info;
    }

    /**
     * 展示密码
     * @param $password
     * @param $id
     * @param $type
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function viewPassword($password, $id, $type)
    {
        $user = CommonService::getUserInfo();
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
            case 'wf_password':
                $enablePassword = $encryption->decrypt($discount['wf_password']);
                break;
            case 'encrypted_answers':
                $enablePassword = $encryption->decrypt($discount['encrypted_answers']);
                break;
        }
        return $enablePassword;
    }

    /**
     * 编辑
     * @param $id
     * @param $status
     * @return bool|false|int
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

    /**
     * 查询条件获取
     * @param $params
     * @return array
     */
    public function getWhere($params)
    {
        $where = [];
        if (isset($params['status']) && ($params['status'] !== '')) {
            $where['a.status'] = ['eq', $params['status']];
        }

        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'ip_name':
                    $where['b.name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'ip_address':
                    $where['b.ip'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'wf_account':
                    $where['a.wf_account'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }

        if (isset($params['belong']) && ($params['belong'] !== '')) {
            $where['a.belong'] = ['eq', $params['belong']];
        }

        if (isset($params['operator_id']) && ($params['operator_id'] !== '')) {
            $where['a.operator_id'] = ['eq', $params['operator_id']];
        }

        return $where;
    }


}