<?php

namespace app\index\service;

use think\Db;
use think\Exception;
use app\common\model\Ali1688Account as Ali1688AccountModel;
use app\common\validate\Ali1688Account as Ali1688AccountValidate;
use think\Config;
use service\ali1688\Ali1688Api;

/**
 * @desc 1688 账号管理
 * @author Jimmy <554511322@qq.com>
 * @date 2018-01-19 10:09:11
 */
class Ali1688AccountService
{

    private $where = []; //查询条件
    private $field = []; //数据库表字段
    private $model = null; //对应的数据库表

    public function __construct()
    {
        $this->model = new Ali1688AccountModel();
        $this->field = $this->model->getFields();
    }

    /**
     * @desc 查询条件
     * @param array $params 请求过来的查询数据
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 10:13:11
     */
    public function getWhere(array $params)
    {
        $this->getFieldWhere($params);
        //账号或代码的搜索
        $type = param($params, 'type');
        $text = param($params, 'text');
        if ($type && $text) {
            $this->where[$type]=['like',"%$text%"];
        }
        //单据的创建时间
        $createTimeFrom = (param($params, 'create_time_from'));
        $createTimeTo = (param($params, 'create_time_to') ?: date('Y-m-d'));
        if ($createTimeFrom && $createTimeTo) {
            $this->where['create_time'] = ['between', [strtotime($createTimeFrom . ' 00:00:00'), strtotime($createTimeTo . ' 23:59:59')]];
        }
    }

    /**
     * @desc 根据提交的参数对比数据库表字段，如果有值就自动添加到 where 查询条件中
     * @param array $params 前端提交过来的查询数据信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 10:14:11
     */
    private function getFieldWhere(array $params)
    {
        foreach ($params as $key => $val) {
            if (in_array($key, $this->field) && $val != '') {
                $this->where[$key] = trim($val);
            }
        }
    }

    /**
     * @desc 获取指定条件的查询数据信息
     * @param array $where 查询条件
     * @param array|string $field 查询的字段
     * @param int $page 起始页
     * @param int $pageSize 每页取多少条数据
     * @param array|string $group 分组字段
     * @param string $order 排序字段
     * @return array 查询的结果数据集
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 10:14:11
     */
    public function getList(array $where, $field = '*', $page = 1, $pageSize = 10, $group = '', $order = 'id desc')
    {
        $map = array_merge($where, $this->where);
        $count = $this->model::where($map)->count();
        //没有数据就返回
        if (!$count) {
            return ['count' => 0, 'data' => []];
        }
        //有数据就取出
        $list = $this->model::where($map)->field($field)->page($page, $pageSize)->group($group)->order($order)->select();
        return ['count' => $count, 'data' => $list];
    }

    /**
     * @desc 根据id获取信息
     * @param array $map 查询条件
     * @return obj $data 查询的结果数据信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 11:16:11
     */
    public function read($map)
    {
        try {
            $res = $this->model::get($map);
            if (!$res) {
                return [];
            }
            return $res;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 新增1688账号
     * @param array $data 前端提交的账号数据
     * @param int $userId 用户ID
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 15:13:11
     */
    public function save($data, $userId)
    {
        try {
            //验证数据的有效性
            $this->validate($data);
            //赋值
            $this->model->code = param($data, 'code');
            $this->model->account_name = param($data, 'account_name');
            $this->model->membership = param($data, 'membership', 4);
            $this->model->enabled = param($data, 'enabled', 1);
            $this->model->is_invalid = param($data, 'is_invalid', 1);
            $this->model->is_authorization = param($data, 'is_authorization', 0);
            $this->model->creator_id = $userId;
            $this->model->client_id = param($data, 'client_id', '');
            $this->model->client_secret = param($data, 'client_secret', '');
            $this->model->access_token = param($data, 'access_token', '');
            $this->model->refresh_token = param($data, 'refresh_token', '');
            $this->model->expiry_time = param($data, 'expiry_time', '');
            $this->model->order_prefix = param($data, 'order_prefix', '');
            $this->model->order_prefix = param($data, 'order_prefix', '');
            //插入数据
            $this->model->allowField(true)->isUpdate(false)->save();
            //成功就提交事务
            return $this->model;
        } catch (Exception $ex) {
            //异常回滚
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 验证数据信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 15:16:11
     */
    private function validate($data)
    {
        try {
            //实例化验证类
            $validate = new Ali1688AccountValidate();
            //对数据进行批量验证
            if (!$validate->scene('create')->check($data)) {
                $error = is_array($validate->getError()) ? implode(' ', $validate->getError()) : $validate->getError();
                throw new Exception($error);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 更新
     * @param array $data 更新的数据信息
     * @param int $userId 用户ID
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 19:46:11
     */
    public function update($data, $userId)
    {
        try {
            //验证数据的有效性
            $this->validate($data);
            $model = $this->model->get($data['id']);
            if (!$model) {
                throw new Exception('数据不存在!');
            }
            //赋值
            $model->code = param($data, 'code');
            $model->account_name = param($data, 'account_name');
            $model->membership = param($data, 'membership', 4);
            $model->enabled = param($data, 'enabled', 1);
            $model->is_invalid = param($data, 'is_invalid', 1);
            $model->is_authorization = param($data, 'is_authorization', 0);
            $model->updator_id = $userId;
            $model->client_id = param($data, 'client_id', '');
            $model->client_secret = param($data, 'client_secret', '');
            $model->access_token = param($data, 'access_token', '');
            $model->refresh_token = param($data, 'refresh_token', '');
            $model->expiry_time = param($data, 'expiry_time', '');
            $model->order_prefix = param($data, 'order_prefix', '');
            $model->order_prefix = param($data, 'order_prefix', '');
            //插入数据
            $model->allowField(true)->isUpdate(true)->save();
            //成功就提交事务
            return $model;
        } catch (Exception $ex) {
            //异常回滚
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 启用停用
     * @param array $data ['id'=>123,'is_invalid'=>1]
     * @param int $userId 用户ID
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 19:50:11
     */
    public function isInvalid($data, $userId)
    {
        try {
            $model = $this->model->get($data['id']);
            if (!$model) {
                throw new Exception('数据不存在!');
            }
            $model->updator_id = $userId;
            $model->is_invalid = $data['is_invalid'];
            $model->allowField(true)->isUpdate(true)->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 获取授权码地址
     * @param array $params 用户提交过来的数据信息['client_id'=>123,'client_secret'=>23]
     * @return string $url 获取授权码的地址
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-22 14:43:11
     */
    public function getAuthorCode($params)
    {
        try {
            $data['redirect_uri'] = Config::get('redirect_uri');
            $data['client_id'] = param($params, 'client_id');
            $data['site'] = '1688';
            $data['client_secret'] = param($params, 'client_secret');
            $url = Ali1688Api::instance($data)->loader('common')->getCodeUrl($data, 'zrzsoft');
            return $url;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 获取Token
     * @param array $params 前端提交的请求数据 ['id'=>123,'client_id'=>'asdf','client_secret'=>'','code'=>'']
     * @param int $userId 用户ID
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-22 17:39:11
     */
    public function getToken($params, $userId)
    {
        try {
            //组装请求数据
            $data = [
                'client_id' => param($params, 'client_id'),
                'client_secret' => param($params, 'client_secret'),
                'code' => param($params, 'code'),
                'redirect_uri' => Config::get('redirect_uri'),
            ];
            //发起请求
            $result = Ali1688Api::instance($data)->loader('common')->getToken($data);
            //处理结果数据
            if ($result && isset($result['access_token'])) {
                $this->updateAccount($params['id'], array_merge($data, $result), $userId);
            } else {
                throw new Exception($result['error_description']);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 更新账号的授权信息
     * @param int $id Description
     * @param array $data 用户请求返回的数据
     * @param int $userId 用户ID
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-22 18:06:11
     */
    public function updateAccount($id, $data, $userId)
    {
        try {
            $model = Ali1688AccountModel::get($id);
            $model->client_id = $data['client_id'];
            $model->client_secret = $data['client_secret'];
            $model->access_token = $data['access_token'];
            $model->refresh_token = $data['refresh_token'];
            $model->expiry_time = $this->convertTime($data['refresh_token_timeout']);
            $model->is_authorization = 1;
            $model->enabled = 1;
            $model->updator_id = $userId;
            //更新数据
            $model->allowField(true)->isUpdate(true)->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 时间转换
     * @param string $time
     * 将此格式 20170326010506000-0700转成时间戳
     */
    private function convertTime($time = '', $separator = '-')
    {
        $b = substr($time, 0, 4) . $separator . substr($time, 4, 2) . $separator . substr($time, 6, 2) . ' ' . substr($time, 8, 2) . ':' . substr($time, 10, 2) . ':' . substr($time, 12, 2);
        return strtotime($b);
    }

    public function getAccount()
    {
        $account_arr = [];
        $accountData = $this->model->field('id,account_name')->select();
        foreach ($accountData as $account){
            $account_arr[] = [
              'label'=> $account['account_name'],
              'value'=> $account['id'],
            ];
        }
        return $account_arr;
    }

}
