<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\yandex\YandexAccount as  YandexAccountModel;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use yandex\YandexOrderApi;

//use walmart\WalmartAccountApi;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/6/7
 * Time: 11:43
 */
class YandexAccountService
{
    protected $yandexAccountModel;
    protected $error = '';

    public function __construct()
    {
        if (is_null($this->yandexAccountModel)) {
            $this->yandexAccountModel = new YandexAccountModel();
        }
    }

    /**
     * 得到错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 账号列表
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function accountList($params = [], $page = 1, $pageSize = 10)
    {

        $where = $this->getWhere($params);

        $field = 'id,account_name,code,shop_id,is_invalid status,create_time,update_time,is_authorization,sync_delivery,download_order,download_listing';
        $sort = "create_time desc";
        //排序刷选
        if (param($params, 'sort_type') && in_array($params['sort_type'], ['account_name', 'code', 'created_at'])) {
            $sort_by = $params['sort_val'] == 2 ? 'DESC' : ' ';
            $sort = $params['sort_type'] . " " . $sort_by . " ,create_time desc";
            unset($sort_by);
        }

        $count = $this->yandexAccountModel->field($field)->where($where)->count();
        $accountList = $this->yandexAccountModel->field($field)->where($where)->order($sort)->page($page, $pageSize)->select();

        $result = [
            'data' => $accountList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 新增
     * @param $data
     * @return bool
     */
    public function add($data, $uid = 0)
    {
        try {
            $id = $data['id'] ?? 0;
            $time = time();

            $save_data['update_time'] = $time;
            $save_data['code'] = $data['code'];
            $save_data['shop_id'] = $data['shop_id'] ?? 0;
            $save_data['download_order'] = $data['download_order'] ?? 0;
            $save_data['download_listing'] = $data['download_listing'] ?? 0;
            $save_data['sync_delivery'] = $data['sync_delivery'] ?? 0;
            $save_data['base_account_id'] = $data['base_account_id'] ?? 0;
            if ($id == 0) {
                //检查产品是否已存在
                if ($this->yandexAccountModel->check(['account_name' => $data['account_name']])) {
                    $this->error = $data['account_name'].'账号已经存在无法重复添加';
                    return false;
                }
                if ($this->yandexAccountModel->check(['code' => $data['code']])) {
                    $this->error =  $data['code'].'yandex简称已经存在无法重复添加';
                    return false;
                }
                $save_data['account_name'] = $data['account_name'];
                $save_data['create_time'] = $time;
                $save_data['creator_id'] = $uid;
                //必须要去账号基础资料里备案
                \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Yandex,$data['code']);

            } else {
                $is_ok = $this->yandexAccountModel->field('id')->where(['code' => $data['code']])->where('id', '<>', $id)->find();
                if ($is_ok) {
                    $this->error = 'yandex简称已经存在无法修改';
                    return false;
                }

                $save_data['id'] = $id;
                $save_data['updater_id'] = $uid;
                //更新缓存
                $cache = Cache::store('YandexAccount');
                foreach ($save_data as $key => $val) {
                    $cache->updateTableRecord($id, $key, $val);
                }
            }
            $this->yandexAccountModel->add($save_data);
            return $this->getOne($id);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 获取账号信息
     * @param $id
     * @return array
     */
    public function getOne($id)
    {
        $field = 'id,account_name,code,shop_id,is_invalid status,create_time,update_time,is_authorization,sync_delivery,download_order,download_listing';
        if ($id == 0) {
            return $this->yandexAccountModel->field($field)->order('id desc')->find();
        }
        return $this->yandexAccountModel->where('id', $id)->field($field)->find();
    }

    /**
     * 获取订单授权信息
     * @param $id
     * @return array
     */
    public function getTokenOne($id)
    {
        return $this->yandexAccountModel->where('id', $id)->field('id,code,account_name,client_id,client_secret')->find();
    }

    /**
     * 封装where条件
     * @param array $params
     * @return array
     */
    public function getWhere($params = [])
    {
        $where = [];
        if (isset($params['status'])) {
            if($params['status'] == 'true'){
                $where['is_invalid'] = ['eq', 1];
            }elseif($params['status'] == 'false'){
                $where['is_invalid'] = ['eq', 0];
            }
        }

        if (isset($params['authorization']) && $params['authorization'] > -1 && $params['authorization']!='') {
            $where['is_authorization'] = ['eq', $params['authorization']];
        }
        if (isset($params['authorization_cat']) && $params['authorization_cat'] > -1) {
            $where['is_authorization_cat'] = ['eq', $params['authorization_cat']];
        }
        if (isset($params['download_order']) && $params['download_order'] > -1) {
            if (empty($params['download_order'])) {
                $where['download_order'] = ['eq', 0];
            } else {
                $where['download_order'] = ['>', 0];
            }
        }
        if (isset($params['download_listing']) && $params['download_listing'] > -1) {
            if (empty($params['download_listing'])) {
                $where['download_listing'] = ['eq', 0];
            } else {
                $where['download_listing'] = ['>', 0];
            }
        }
        if (isset($params['sync_delivery']) && $params['sync_delivery'] > -1) {
            if (empty($params['sync_delivery'])) {
                $where['sync_delivery'] = ['eq', 0];
            } else {
                $where['sync_delivery'] = ['>', 0];
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'account_name':
                    $where['account_name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'code':
                    $where['code'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }
        if (isset($params['taskName']) && isset($params['taskCondition']) && isset($params['taskTime']) && $params['taskName'] !== '' && $params['taskTime'] !== '') {
            $where[$params['taskName']] = [trim($params['taskCondition']), $params['taskTime']];
        }
        return $where;
    }

    /**
     * 状态
     * @param $data
     * @return array
     */
    public function changeStatus($data)
    {
        $cache = Cache::store('YandexAccount');
        $account = $cache->getAccount($data['id']);

        if (!isset($account)) {
            $this->error = '账号不存在';
            return false;
        }
        try {
            $updata = [];
            if (isset($data['status'])) {
                $updata['is_invalid'] = $data['status'];
            }
            $updata['update_time'] = time();
            $this->yandexAccountModel->allowField(true)->save($updata, ['id' => $data['id']]);
            //修改缓存
            foreach ($updata as $key => $val) {
                $cache->updateTableRecord($data['id'], $key, $val);
            }
            return true;
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }

    /**
     * 更新拉取数据必要参数
     * @param $data
     * @param $uid
     * @return array
     */
    public function updateToken($data, $uid = 0)
    {
        if (empty($data['client_id']) || empty($data['client_secret']) || empty($data['codes']) ) {
            $this->error = '帐号授权信息不完整';
            return false;
        }
        $cache = Cache::store('YandexAccount');
        $account = $cache->getAccount($data['id']);
        if (!isset($account)) {
            $this->error = '账号不存在';
            return false;
        }
        try {
            $save_data['id'] = $data['id'];
            $account['client_id'] = $save_data['client_id'] = $data['client_id'];
            $account['client_secret'] = $save_data['client_secret'] = $data['client_secret'];
            $account['is_authorization'] = $save_data['is_authorization'] =1;
            //更新缓存
            foreach ($save_data as $key => $val) {
                $cache->updateTableRecord($data['id'], $key, $val);
            }
            $this->yandexAccountModel->add($save_data);
            $account['codes'] = $data['codes'];
            (new YandexOrderApi($account))->getAccountToken();
            return true;
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }

    /**
     * 更新token
     * @param $id
     * @param $accessToken
     * @return bool
     * @throws \think\Exception
     */
    public function updateAccessToken($id,$data)
    {
        $cache = Cache::store('YandexAccount');
        $where['id'] = $id;
        $save_data['access_token'] = $data['access_token'];
        $save_data['refresh_token'] = $data['refresh_token'];
        $save_data['expiry_time'] = $data['expires_in'] + time();
        $save_data['is_authorization'] = 1;
        $save_data['update_time'] = time();
        $this->yandexAccountModel->save($save_data,$where);
        //更新缓存
        foreach ($save_data as $key => $val) {
            $cache->updateTableRecord($id, $key, $val);
        }
        return true;
    }


}