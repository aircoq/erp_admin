<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\souq\SouqAccount;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use cd\CdBaseApi;
use cd\CdOrderApi;
use think\Request;
use think\Db;

/**
 * Created by PhpStorm.
 * User: lanshushu
 * Date: 2017/5/25
 * Time: 11:17
 */
class SouqAccountService
{
    protected $souqAccountModel;
    protected $error = '';

    public function __construct()
    {
        if (is_null($this->souqAccountModel)) {
            $this->souqAccountModel = new SouqAccount();
        }
    }

    public function getError()
    {
        return $this->error;
    }


    /** 账号列表
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function accountList($params = [], $page = 1, $pageSize = 10)
    {
        $where = $this->getWhere($params);
        $field = 'id,account_name,code,is_invalid status,create_time,is_authorization,sync_delivery,download_order,download_listing';
        $sort = "create_time desc";
        //排序刷选
        if (param($params, 'sort_type') && in_array($params['sort_type'], ['account_name', 'code', 'created_at'])) {
            $sort_by = $params['sort_val'] == 2 ? 'DESC' : ' ';
            $sort = $params['sort_type'] . " " . $sort_by . " ,create_time desc";
            unset($sort_by);
        }

        $count = $this->souqAccountModel->field($field)->where($where)->count();
        $accountList = $this->souqAccountModel->field($field)->where($where)->order($sort)->page($page, $pageSize)->select();

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
            $save_data['code'] = $data['code'];
            $save_data['updated_time'] = $time;
            $save_data['download_order'] = $data['download_order'] ?? 0;
            $save_data['download_listing'] = $data['download_listing'] ?? 0;
            $save_data['sync_delivery'] = $data['sync_delivery'] ?? 0;

            if ($id == 0) {
                //必须要去账号基础资料里备案
                \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Souq,$data['code']);
                //检查产品是否已存在
                if ($this->souqAccountModel->check(['account_name' => $data['account_name']])) {
                    $this->error = 'fummart账号已经存在无法重复添加';
                    return false;
                }
                if ($this->souqAccountModel->check(['code' => $data['code']])) {
                    $this->error = 'fummart简称已经存在无法重复添加';
                    return false;
                }
                $save_data['account_name'] = $data['account_name'];
                $save_data['create_time'] = $time;
                $save_data['created_user_id'] = $uid;


            } else{
                $is_ok = $this->souqAccountModel->field('id')->where(['code' => $data['code']])->where('id','<>',$id)->find();

                if($is_ok){
                    $this->error = $data['code'].'简称已经存在无法修改';
                    return false;
                }
                $save_data['id'] = $id;

                //更新缓存
                $cache = Cache::store('SouqAccount');
                foreach ($save_data as $key => $val) {

                    $cache->updateTableRecord($id, $key, $val);
                }
            }
            $this->souqAccountModel->add($save_data);
            return $this->getOne($id);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }


    /** 获取账号信息
     * @param $id
     * @return array
     */
    public function getOne($id)
    {
        // echo"111";die;
        $field = 'id,account_name,code,is_invalid status,create_time,is_authorization,sync_delivery,download_order,download_listing';
        if ($id == 0) {
            return $this->souqAccountModel->field($field)->order('id desc')->find();
        }
        return $this->souqAccountModel->where('id', $id)->field($field)->find();
    }

    /** 获取订单授权信息
     * @param $id
     * @return array
     */
    public function getTokenOne($id)
    {
        return $this->souqAccountModel->where('id', $id)->field('id,code,account_name,access_key_id,secret_key')->find();
    }


    /**
     * 封装where条件
     * @param array $params
     * @return array
     */
    function getWhere($params = [])
    {

        $where = [];
        if (isset($params['status']) && $params['status'] != '' ) {
            $params['status'] = $params['status'] == 'true' ? 1 : 0;
            $where['is_invalid'] = ['eq', $params['status']];
        }

        if (isset($params['authorization']) && $params['authorization'] > -1&& $params['authorization']!='') {
            $where['is_authorization'] = ['eq', $params['authorization']];
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




}