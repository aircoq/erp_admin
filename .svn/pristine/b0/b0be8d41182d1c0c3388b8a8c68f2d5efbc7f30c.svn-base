<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/8/18
 * Time: 11:29
 */

namespace app\index\service;


use think\Db;
use think\Exception;
use think\Request;
use paypal\PaypalRestful;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\service\Common as CommonService;
use app\common\service\Encryption;
use app\common\model\Server;
use app\common\model\User;
use app\common\model\paypal\PaypalAccount;
use app\common\model\account\PaypalWithdrawalsType;


class PaypalAccountService
{

    protected $withdrawals_type = [
        [
            'id' => 1,
            'name' => 'P卡',
        ], [
            'id' => 2,
            'name' => '连连',
        ], [
            'id' => 3,
            'name' => 'PingPong',
        ], [
            'id' => 4,
            'name' => 'WF万里汇',
        ], [
            'id' => 5,
            'name' => '香港花旗银行',
        ], [
            'id' => 6,
            'name' => '香港工商银行',
        ],
    ];

    public $model = null;
    public $withdrawalsModel = null;

    public function __construct()
    {
        empty($this->model) && $this->model = new PaypalAccount();
        empty($this->withdrawalsModel) && $this->withdrawalsModel = new PaypalWithdrawalsType();
    }

    /**
     * paypal帐号列表
     * @return array
     * @throws Exception
     */
    public function getLists()
    {
        $request = Request::instance();
        if (isset($request->header()['X-Result-Fields'])) {
            $field = $request->header()['X-Result-Fields'];
        }

        $params = $request->param();

        $order = 'id';
        $sort = 'desc';
        if (!empty($params['order_by']) && in_array($params['order_by'], ['account_name', 'api_user_name'])) {
            $order = $params['order_by'];
        }
        if (!empty($params['sort']) && in_array($params['sort'], ['asc', 'desc'])) {
            $sort = $params['sort'];
        }

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 25);
        $where = $this->getWhere($params);

        $count = $this->model->alias('a')->join('server b', 'a.server_id=b.id','LEFT')->where($where)->count();
        $field = 'a.id,a.code,a.account_name,a.is_invalid,a.status,a.paypal_authorized,a.rest_authorized,a.api_user_name,a.min_amout,
        a.max_amout,a.download_paypal,a.download_dispute,a.download_email,a.create_time,a.updated_time,a.server_id,a.type,
        a.credit_card,a.client,a.operator_id,a.withdrawals_type,b.name ip_name,b.ip ip_address';
        $accountData = $this->model
            ->alias('a')
            ->join('server b', 'a.server_id=b.id', 'LEFT')
            ->field($field)
            ->where($where)
            ->order($order, $sort)
            ->page($page, $pageSize)
            ->select();

        $temp = [];
        foreach ($this->withdrawals_type as $key => $item) {
            $temp[$item['id']] = $item['name'];
        }

        foreach ($accountData as $k => $v) {
            $tempArr = [];
            $withdrawalsType = explode(',', $v['withdrawals_type']);
            foreach ($withdrawalsType as $item) {
                if (isset($temp[$item])) {
                    array_push($tempArr, $temp[$item]);
                }
            }
            $v['withdrawals_type'] = implode(',', $tempArr);
            $accountData[$k]['operator'] = Cache::store('User')->getOneUserRealname($v['operator_id']);
        }

        $result = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'order_by' => $order,
            'sort' => $sort,
            'data' => $accountData
        ];

        return $result;
    }

    /**
     * 返回提款类型
     * @return array
     * @author zhuda
     */
    public function withdrawalsType()
    {
        return $this->withdrawals_type;
    }

    /**
     * 获取paypal账号信息（多条）
     * @param $data
     * @return false|\PDOStatement|string|\think\Collection
     * @author zhuda
     * @throws \think\exception\DbException
     */
    public function getPaypalInfo($data)
    {
        $where = [];
        if (!empty($data['server_id'])) {
            $where['a.server_id'] = $data['server_id'];
        }

        if (!empty($data['user_id'])) {
            $where['b.user_id'] = $data['user_id'];
        }

        $result = $this->model
            ->alias('a')
            ->join('paypal_member b', 'a.id=b.paypal_account_id', 'LEFT')
            ->where($where)
            ->select();
        return $result;
    }


    /**
     * 获取查询条件
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWhere($params)
    {
        $where = [];
        if (isset($params['operator_id']) && ($params['operator_id'] !== '')) {
            $where['paypal_account.operator_id'] = ['eq', $params['operator_id']];
        }

        if (isset($params['type']) && ($params['type'] !== '')) {
            $where['paypal_account.type'] = ['eq', $params['type']];
        }

        if (isset($params['withdrawals_type']) && ($params['withdrawals_type'] !== '')) {
            $where['paypal_account.withdrawals_type'] = ['like', '%' . $params['withdrawals_type'] . '%'];
        }

        if (isset($params['is_invalid']) && $params['is_invalid'] !== '') {
            if ($params['is_invalid'] == 0) {
                $where['is_invalid'] = ['EQ', 0];
            } elseif ($params['is_invalid'] == 1) {
                $where['is_invalid'] = ['EQ', 1];
            }
        }

        if (isset($params['snType']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'account_name':
                    $where['account_name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'api_user_name':
                    $where['api_user_name'] = ['EQ', $params['snText']];
                    break;
                case 'ip_name':
                    $result = (new Server())->field('id')->where(['name' => ['like', '%' . $params['snText'] . '%']])->select();
                    if ($result) {
                        $idsArr = [];
                        foreach ($result as $item) {
                            array_push($idsArr, $item['id']);
                        }
                        $where['server_id'] = ['in', implode(',', $idsArr)];
                    }
                    break;
                case 'username':
                    $where['username'] = ['EQ', $params['snText']];
                    break;
                case 'realname':
                    $where['realname'] = ['EQ', $params['snText']];
                    break;
                default:
                    break;
            }
        }
        if (!empty($params['auth_name']) && isset($params['auth_status']) && $params['auth_status'] !== '') {
            $where[$params['auth_name']] = ['EQ', $params['auth_status']];
        }

        //日期查询
        if (!empty($params['snDate'])) {
            $date_condition = [];
            if (!empty($params['date_b']) && !empty($params['date_e'])) {
                $date_condition = ['BETWEEN', [strtotime($params['date_b']), strtotime($params['date_e']) + 86400]];
            } else if (!empty($params['date_b']) && empty($params['date_e'])) {
                $date_condition = ['>', strtotime($params['date_b'])];
            } else if (empty($params['date_b']) && !empty($params['date_e'])) {
                $date_condition = ['<', strtotime($params['date_e']) + 86400];
            }
            if (!empty($date_condition)) {
                switch ($params['snDate']) {
                    case 'create_date':
                        $where['create_time'] = $date_condition;
                        break;
                    case 'update_date':
                        $where['updated_time'] = $date_condition;
                        break;
                    default:
                        break;
                }
            }
        }

        if (isset($params['taskName']) && isset($params['taskCondition']) && isset($params['taskTime']) &&
            $params['taskName'] !== '' && $params['taskTime'] !== '') {
            $where[$params['taskName']] = [trim($params['taskCondition']), $params['taskTime']];
        }
        return $where;
    }


    /**
     * 保存paypal帐号
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data['account_name'] = trim(param($params, 'account_name'));

        if ($this->model->where(['account_name' => trim($data['account_name'])])->find()) {
            throw new Exception('账户名重复');
        }

        $withdrawalsAccounts = json_decode($request->post('withdrawals_accounts'), true);
        if (empty($withdrawalsAccounts)) {
            throw new Exception('提款账户必填');
        }

        $data['api_user_name'] = trim(param($params, 'api_user_name'));
        $data['api_secret'] = trim(param($params, 'api_secret'));
        $data['api_signature'] = trim(param($params, 'api_signature'));
        $data['paypal_authorized'] = 0;
        $time = time();
        if (!empty($data['api_secret']) && !empty($data['api_signature'])) {
            $data['paypal_authorized'] = 1;
            $data['paypal_authorized_time'] = $time;
        }
        //密码；
        $data['email_password'] = trim(param($params, 'email_password'));
        if (!empty($data['email_password'])) {
            $encryption = new Encryption();
            $data['email_password'] = $encryption->encrypt($data['email_password']);
        }

        $data['rest_client_id'] = $request->post('rest_client_id', '');
        $data['rest_secret'] = $request->post('rest_secret', '');
        $data['rest_authorized'] = 0;
        if (!empty($data['rest_client_id']) && !empty($data['rest_secret'])) {
            $data['rest_authorized'] = 1;
        }

        $data['status'] = 0;
        if ($data['paypal_authorized'] || $data['rest_authorized']) {
            $data['status'] = 1;
        }

        $user = CommonService::getUserInfo($request);
        $data['created_user_id'] = $user['user_id'];
        $data['updated_user_id'] = $user['user_id'];
        $data['create_time'] = $time;
        $data['updated_time'] = $time;
        $data['download_paypal'] = $request->post('download_paypal', 0);
        $data['download_dispute'] = $request->post('download_dispute', 0);
        $data['download_email'] = $request->post('download_email', 0);
        $data['server_id'] = $request->post('server_id');
        $data['belong'] = $request->post('belong');
        $data['phone'] = $request->post('phone');
        $data['type'] = $request->post('type');
        $data['credit_card'] = $request->post('credit_card');
        $data['client'] = $request->post('client');
        $data['operator_id'] = $request->post('operator_id');
        $data['withdrawals_type'] = $request->post('withdrawals_type');

        Db::startTrans();
        try {
            $this->model->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $this->model->id;
            $time = time();
            foreach ($withdrawalsAccounts as $key => $item) {
                $temp = [];
                $temp['paypal_account_id'] = $new_id;
                $temp['account'] = $item['account'];
                $temp['type_key'] = $item['id'];
                $temp['type_value'] = '';
                $temp['create_id'] = $user['user_id'];
                $temp['create_time'] = $time;
                $this->saveWithdrawals($temp);
            }

            Db::commit();
            //加入缓存
            Cache::store('PaypalAccount')->setTableRecord($new_id);
            $field = 'id,code,account_name,is_invalid,status,paypal_authorized,rest_authorized,api_user_name,min_amout,
            max_amout,download_paypal,download_dispute,create_time,updated_time';
            $newData = $this->model->field($field)->where(['id' => $new_id])->find();
            return ['message' => '新增成功', 'id' => $new_id, 'data' => $newData];
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * paypal_withdrawals_type数据写入
     * @param $date
     * @return false|int|string
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function saveWithdrawals($date)
    {
        $result = $this->withdrawalsModel->add($date);
        if (!$result) {
            throw new Exception('提款账号写入失败!');
        }
        return $result;
    }

    /**
     * @title 删除数据
     * @param $where
     * @return int
     * @throws Exception
     */
    private function delWithdrawals($where)
    {
        $result = $this->withdrawalsModel->where($where)->delete();
        if (!$result) {
            throw new Exception('原数据删除有错误!');
        }
        return $result;
    }


    /**
     * paypal_withdrawals_type数据查询
     * @param $where
     * @param $field
     * @return false|int|string
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function readAccountWithdrawals($where, $field)
    {
        $result = $this->withdrawalsModel->field($field)->where($where)->select();
        return $result;
    }


    /**
     * 更新paypal帐号
     * @param Request $request
     * @param $id
     * @return array
     * @throws Exception
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $paypal = $this->model->where(['id' => $id])->find();

        if (!$paypal) {
            throw new Exception('数据不存在');
        }

        $withdrawalsAccounts = json_decode($request->put('withdrawals_accounts'), true);
        if (empty($withdrawalsAccounts)) {
            throw new Exception('提款账户必填');
        }

        $serverId = $request->put('server_id');
        if (empty($serverId)) {
            throw new Exception('服务器必填');
        }

        //nvp授权，有这两参数，则判断填充rest授权信息；
        $time = time();
        if (isset($params['api_user_name']) && isset($params['api_secret']) && isset($params['api_signature'])) {
            $data['api_user_name'] = trim($params['api_user_name']);
            $data['api_secret'] = trim($params['api_secret']);
            $data['api_signature'] = trim($params['api_signature']);
            $data['paypal_authorized'] = 0;
            $data['paypal_authorized_time'] = 0;
            if (!empty($data['api_secret']) && !empty($data['api_signature'])) {
                $data['paypal_authorized'] = 1;
                $data['paypal_authorized_time'] = $time;
            }
        }

        //rest授权，有这两参数，则判断填充rest授权信息；
        if (isset($params['rest_client_id']) && isset($params['rest_secret'])) {
            $data['rest_client_id'] = empty($params['rest_client_id']) ? '' : trim($params['rest_client_id']);
            $data['rest_secret'] = empty($params['rest_secret']) ? '' : trim($params['rest_secret']);
            $data['rest_authorized'] = 0;
            if (!empty($data['rest_client_id']) && !empty($data['rest_secret'])) {
                $data['rest_authorized'] = 1;
            }
        }

        $data['email_password'] = trim(param($params, 'email_password'));
        if (!empty($data['email_password']) && $data['email_password'] !== $paypal['email_password']) {
            $encryption = new Encryption();
            $data['email_password'] = $encryption->encrypt($data['email_password']);
        }
        $data['status'] = 0;
        if (!empty($data['paypal_authorized']) || !empty($data['rest_authorized'])) {
            $data['status'] = 1;
        }

        $user = CommonService::getUserInfo($request);
        $data['updated_time'] = $time;
        $data['updated_user_id'] = $user['user_id'];
        $data['download_paypal'] = $params['download_paypal'] ?? 0;
        $data['download_dispute'] = empty($params['download_dispute']) ? 0 : intval($params['download_dispute']);
        $data['download_email'] = empty($params['download_email']) ? 0 : intval($params['download_email']);
        $data['server_id'] = $request->put('server_id');
        $data['belong'] = $request->put('belong');
        $data['phone'] = $request->put('phone');
        $data['type'] = $request->put('type');
        $data['credit_card'] = $request->put('credit_card');
        $data['client'] = $request->put('client');
        $data['operator_id'] = $request->put('operator_id');
        $data['withdrawals_type'] = $request->put('withdrawals_type');
        if (empty($data)) {
            throw new Exception('数据参数不能为空');
        }

        try {
            Db::startTrans();
            $this->model->allowField(true)->update($data, ['id' => $id]);
            $this->delWithdrawals(['paypal_account_id' => $id]);
            foreach ($withdrawalsAccounts as $key => $item) {
                $temp = [];
                $temp['paypal_account_id'] = $id;
                $temp['account'] = $item['account'];
                $temp['type_key'] = $item['id'];
                $temp['type_value'] = '';
                $temp['create_id'] = $user['user_id'];
                $temp['create_time'] = $time;
                $this->saveWithdrawals($temp);
            }
            Db::commit();

            foreach ($data as $key => $val) {
                Cache::store('PaypalAccount')->updateTableRecord($id, $key, $val);
            }

            $field = 'id,code,account_name,is_invalid,status,paypal_authorized,rest_authorized,api_user_name,min_amout,
            max_amout,download_paypal,download_dispute,create_time,updated_time';
            $newData = $this->model->field($field)->where(['id' => $id])->find();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
        return ['message' => '更新成功', 'data' => $newData];
    }


    /**
     * 更新paypal帐号
     * @param Request $request
     * @param $id
     * @return array
     * @throws Exception
     */
    public function authorization(Request $request, $id)
    {
        $params = $request->param();
        $time = time();

        $data['api_user_name'] = trim($params['api_user_name']);
        $data['api_secret'] = trim($params['api_secret']);
        $data['api_signature'] = trim($params['api_signature']);
        $data['paypal_authorized'] = 0;
        $data['paypal_authorized_time'] = 0;
        if (!empty($data['api_secret']) && !empty($data['api_signature'])) {
            $data['paypal_authorized'] = 1;
            $data['paypal_authorized_time'] = $time;
        }

        //新增3字段；
        $data['rest_client_id'] = empty($params['rest_client_id']) ? '' : trim($params['rest_client_id']);
        $data['rest_secret'] = empty($params['rest_secret']) ? '' : trim($params['rest_secret']);
        $data['rest_authorized'] = 0;
        if (!empty($data['rest_client_id']) && !empty($data['rest_secret'])) {
            $data['rest_authorized'] = 1;
        }
        $data['status'] = 0;
        if ($data['paypal_authorized'] || $data['rest_authorized']) {
            $data['status'] = 1;
        }

        $user = CommonService::getUserInfo($request);
        $data['updated_time'] = $time;
        $data['updated_user_id'] = $user['user_id'];

        //启动事务
        try {
            if (empty($data)) {
                return ['message' => '数据参数不能为空'];
            }
            $this->model->allowField(true)->update($data, ['id' => $id]);

            //更新缓存
            foreach ($data as $key => $val) {
                Cache::store('PaypalAccount')->updateTableRecord($id, $key, $val);
            }
            $field = 'id,code,account_name,is_invalid,status,paypal_authorized,rest_authorized,api_user_name,min_amout,
            max_amout,download_paypal,download_dispute,create_time,updated_time';
            $newData = $this->model->field($field)->where(['id' => $id])->find();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        return ['message' => '更新成功', 'data' => $newData];
    }


    /**
     * 查看密码
     * @param $password
     * @param $account_id
     * @return string
     * @throws \think\exception\DbException
     */
    public function viewPassword($password, $account_id)
    {
        $enablePassword = '';
        $user = CommonService::getUserInfo();
        if (empty($user)) {
            throw new JsonErrorException('非法操作', 400);
        }
        $userModel = new User();
        $userInfo = $userModel->where(['id' => $user['user_id']])->find();
        if (empty($userInfo)) {
            throw new JsonErrorException('外来物种入侵', 500);
        }
        if ($userInfo['password'] != User::getHashPassword($password, $userInfo['salt'])) {
            throw new JsonErrorException('登录密码错误', 500);
        }
        $encryption = new Encryption();
        //查看账号信息
        $accountInfo = $this->model->field('email_password')->where(['id' => $account_id])->find();
        if (empty($accountInfo)) {
            throw new JsonErrorException('账号记录不存在', 500);
        }
        $enablePassword = $encryption->decrypt($accountInfo['email_password']);

        return $enablePassword;
    }


    /**
     * 批量设置
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function batchSet($params)
    {
        //实例化模型
        $model = new PaypalAccount();

        if (isset($params['is_invalid']) && $params['is_invalid'] != '') {
            $data['is_invalid'] = (int)$params['is_invalid'];   //1 启用， 0未启用
        }
        if (isset($params['download_paypal']) && $params['download_paypal'] != '') {
            $data['download_paypal'] = (int)$params['download_paypal'];
        }
        if (isset($params['download_dispute']) && $params['download_dispute'] != '') {
            $data['download_dispute'] = (int)$params['download_dispute'];
        }
        if (isset($params['download_email']) && $params['download_email'] != '') {
            $data['download_email'] = (int)$params['download_email'];
        }
        $idArr = array_merge(array_filter(array_unique(explode(',', $params['ids']))));

        $user = CommonService::getUserInfo();
        $data['updated_user_id'] = $user['user_id'];
        $data['updated_time'] = time();

        //开启事务
        try {
            Db::startTrans();
            if (empty($data)) {
                throw new Exception('数据参数不能为空');
            }
            $model->allowField(true)->update($data, ['id' => ['in', $idArr]]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }

        //更新缓存
        $cache = Cache::store('PaypalAccount');
        foreach ($idArr as $id) {
            foreach ($data as $k => $v) {
                $cache->updateTableRecord($id, $k, $v);
            }
        }
        return true;
    }

    public function getEvents($id)
    {

        $call = new PaypalRestful($id);
        $events = $call->getEvents();

        return [];
    }

    public function setEvents()
    {
        return [];
    }


}