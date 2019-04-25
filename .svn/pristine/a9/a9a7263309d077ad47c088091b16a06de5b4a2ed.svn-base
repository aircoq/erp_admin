<?php
namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\LocalBuyerAccount;
use app\common\model\User;
use app\common\service\Common;
use think\Exception;

/**
 * 本地买手管理
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/30
 * Time: 14:57
 */
class LocalBuyerAccountService
{
    protected $localBuyerAccountModel;

    public function __construct()
    {
        if (is_null($this->localBuyerAccountModel)) {
            $this->localBuyerAccountModel = new LocalBuyerAccount();
        }
    }

    /**
     * 买手列表
     * @param $where
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function lists($where, $page = 0, $pageSize = 10)
    {
        $field = 'l.id,l.channel_id,l.email,l.username,l.status,l.account_creator,l.account_create_time,l.update_time,s.name as server_name';
        $join[] = ['server s', 's.id = l.server_id', 'left'];
        $count = $this->localBuyerAccountModel->alias('l')->field($field)->join($join)->where($where)->count();
        $buyerList = $this->localBuyerAccountModel->alias('l')->field($field)->join($join)->where($where)->page($page,
            $pageSize)->select();
        $buyerData = [];
        foreach ($buyerList as $key => $value) {
            $value = $value->toArray();
            $userInfo = Cache::store('user')->getOneUser($value['account_creator']);
            if (!empty($userInfo)) {
                $value['account_creator'] = $userInfo['realname'];
            }
            $value['status'] = empty($value['status']) ? '正常' : '作废';
            $value['channel_id'] = !empty($value['channel_id']) ? Cache::store('channel')->getChannelName($value['channel_id']) : '';
            array_push($buyerData, $value);
        }
        $result = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => $buyerData
        ];
        return $result;
    }

    /**
     * 获取详情信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function info($id)
    {
        $field = 'id,channel_id,username,status,server_id,email,email_password,password,account_creator,account_create_time';
        $info = $this->localBuyerAccountModel->field($field)->where(['id' => $id])->find();
        $userInfo = Cache::store('user')->getOneUser($info['account_creator']);
        $temp['account_creator'] = $info['account_creator'];
        $temp['account_creator_name'] = '';
        if (!empty($userInfo)) {
            $temp['account_creator_name'] = $userInfo['realname'];
        }
        $info['account'] = $temp;
        return $info;
    }

    /**
     * 更新
     * @param $data
     * @param $id
     */
    public function update($data, $id)
    {
        if (!checkStringIsBase64($data['password'])) {
            $data['password'] = base64_encode($data['password']);
        }
        if (!checkStringIsBase64($data['email_password'])) {
            $data['email_password'] = base64_encode($data['email_password']);
        }
        if (isset($data['account_create_time'])) {
            $data['account_create_time'] = strtotime($data['account_create_time']);
        }
        try {
            $this->localBuyerAccountModel->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 新增
     * @param $data
     * @return bool
     */
    public function add($data)
    {
        try {
            $data['password'] = base64_encode($data['password']);
            $data['email_password'] = base64_encode($data['email_password']);
            if (isset($data['account_create_time'])) {
                $data['account_create_time'] = strtotime($data['account_create_time']);
            }
            $this->localBuyerAccountModel->allowField(true)->isUpdate(false)->save($data);
            return $this->localBuyerAccountModel->id;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 删除
     * @param $id
     */
    public function batch($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        try {
            $this->localBuyerAccountModel->where('id', 'in', $id)->delete();
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 查看密码
     * @param $password
     * @param $account_id
     * @param $type
     * @return bool|string
     */
    public function viewPassword($password, $account_id, $type)
    {
        $enablePassword = '';
        $user = Common::getUserInfo();
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
        //查看账号信息
        $localAccountInfo = $this->localBuyerAccountModel->field('email_password,password')->where(['id' => $account_id])->find();
        if (empty($localAccountInfo)) {
            throw new JsonErrorException('账号记录不存在', 500);
        }
        switch ($type) {
            case 'email':
                $enablePassword = base64_decode($localAccountInfo['email_password']);
                break;
            case 'login':
                $enablePassword = base64_decode($localAccountInfo['password']);
                break;
        }
        return $enablePassword;
    }
}