<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\lazada\LazadaAccount;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use think\Request;
use service\lazada\LazadaApi;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/25
 * Time: 11:17
 */
class LazadaAccountService
{
    protected $lazadaAccountModel;

    public function __construct()
    {
        if (is_null($this->lazadaAccountModel)) {
            $this->lazadaAccountModel = new LazadaAccount();
        }
    }

    /** 账号列表
     * @param Request $request
     * @return array
     * @throws \think\Exception
     */
    public function accountList(Request $request)
    {
        $where = [];
        $params = $request->param();
        if(isset($params['site'])){
            $where['site'] = ['eq', $params['site']];
        }
        if (isset($params['status'])) {
            $params['status'] = $params['status'] == 1 ? 1 : 0;
            $where['status'] = ['eq', $params['status']];
        }
        if (isset($params['authorization']) && $params['authorization']!='') {
            $params['authorization'] = $params['authorization'] == 0? 0 : 1;
            $where['is_authorization'] = ['eq', $params['authorization']];
        }

        if (isset($params['download_order']) && $params['download_order'] > -1) {
            if(empty($params['download_order'])){
                $where['download_order'] = ['eq', 0];
            }else{
                $where['download_order'] = ['>', 0];
            }
        }
        if (isset($params['download_listing']) && $params['download_listing'] > -1) {
            if(empty($params['download_listing'])){
                $where['download_listing'] = ['eq', 0];
            }else{
                $where['download_listing'] = ['>', 0];
            }
        }
        if (isset($params['sync_delivery']) && $params['sync_delivery'] > -1) {
            if(empty($params['sync_delivery'])){
                $where['sync_delivery'] = ['eq', 0];
            }else{
                $where['sync_delivery'] = ['>', 0];
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'account_name':
                    $where['name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'code':
                    $where['code'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }

        if(isset($params['taskName']) && isset($params['taskCondition']) && isset($params['taskTime']) && $params['taskName'] !== '' && $params['taskTime'] !== '') {
            $where[$params['taskName']] = [trim($params['taskCondition']), $params['taskTime']];
        }
        $orderBy='';
        $orderBy .= fieldSort($params);
        $orderBy .= 'create_time desc,update_time desc';
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $field = 'id,code,name,download_listing,lazada_name,platform_status,status,token_expire_time,refresh_expire_time,site,is_authorization,download_order,sync_delivery,create_time,update_time,app_key,app_secret,access_token';
        $count = $this->lazadaAccountModel->field($field)->where($where)->count();
        $accountList = $this->lazadaAccountModel->field($field)->where($where)->order($orderBy)->page($page, $pageSize)->select();
        $new_array = [];
        foreach ($accountList as $k => $v) {
            $temp = $v->toArray();
            $new_array[$k] = $temp;
        }
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /** 保存账号信息
     * @param $data
     * @return array
     */
    public function save($data)
    {

        $ret = [
            'msg' => '',
            'code' => ''
        ];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['platform_status'] = 1;  //设置为有效
        $lazadaModel = new LazadaAccount();
        Db::startTrans();
        $re = $lazadaModel->where('code', $data['code'])->field('id')->find();
        if (count($re)) {
            $ret['msg'] = '账户名重复';
            $ret['code'] = 400;
            return $ret;
        }
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Lazada, $data['code'], $data['site']);
        try {

            $lazadaModel->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $lazadaModel->id;
            //删除缓存
            Cache::store('lazadaAccount')->delAccount();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
        $accountInfo = $this->lazadaAccountModel->field(true)->where(['id' => $new_id])->find();
        return $accountInfo;
    }

    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id)
    {
        $accountInfo = $this->lazadaAccountModel->field('id,code,name,lazada_name,app_key,site,download_order,sync_delivery,download_listing')->where(['id' => $id])->find();
        if(empty($accountInfo)){
            throw new JsonErrorException('账号不存在',500);
        }
        return $accountInfo;
    }

    /** 更新
     * @param $id
     * @param $data
     * @return \think\response\Json
     */
    public function update($id, $data)
    {

        if ($this->lazadaAccountModel->isHas($id, $data['code'], '')) {
            throw new JsonErrorException('代码或者用户名已存在', 400);
        }
        $model = $this->lazadaAccountModel->get($id);
        Db::startTrans();
        try {
            //赋值
            $model->code = isset($data['code'])?$data['code']:'';
            $model->name = isset($data['name'])?$data['name']:'';
            $model->lazada_name = isset($data['lazada_name'])?$data['lazada_name']:'';
            $model->site = isset($data['site'])?$data['site']:'';
            $model->app_key = isset($data['app_key'])?$data['app_key']:'';
            $model->download_order = isset($data['download_order'])?$data['download_order']:'';
            $model->download_listing = isset($data['download_listing'])?$data['download_listing']:'';
            $model->sync_delivery = isset($data['sync_delivery'])?$data['sync_delivery']:'';
            $model->update_time =time();
            unset($data['id']);

            //插入数据
            $model->allowField(true)->isUpdate(true)->save();

//            $this->lazadaAccountModel->allowField(true)->save($data, ['id' => $id]);
            //删除缓存
            Cache::store('lazadaAccount')->delAccount();
            Db::commit();
            return $model;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }


    /** 批量更新抓取的时间
     * @param $ids
     * @param $data
     * @return \think\response\Json
     */
    public function update_download($ids, $data)
    {
        Db::startTrans();
        try {
            //赋值
            $param['status']= $data['status'] == 1 ? 1 : 0;
            $param['download_order'] = isset($data['download_order'])?$data['download_order']:'';
            $param['download_listing'] = isset($data['download_listing'])?$data['download_listing']:'';
            $param['sync_delivery'] = isset($data['sync_delivery'])?$data['sync_delivery']:'';
            $this->lazadaAccountModel->allowField(true)->update($param,['id' => ['in', json_decode($ids)]]);
            $model = $this->lazadaAccountModel->field('id ,status,download_order,download_listing,sync_delivery')->where(['id' => ['in', json_decode($ids)]])->select();
            unset($ids);
            //删除缓存
            Cache::store('lazadaAccount')->delAccount();
            Db::commit();
            return $model;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /** 状态
     * @param $id
     * @param $data
     * @return array
     */
    public function status($id, $data)
    {
        if (!$this->lazadaAccountModel->check(['id' => $id])) {
            throw new JsonErrorException('账号不存在', 400);
        }
        try {
            $data['update_time'] = time();
            $this->lazadaAccountModel->allowField(true)->save($data, ['id' => $id]);
            //删除缓存
            Cache::store('lazadaAccount')->delAccount();
            return true;
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }

    /** 获取Token
     * @param $id
     * @param $data
     * @return array
     * @throws \think\Exception
     */
    public function getToken($id, $data)
    {
        $result = LazadaApi::handler('common')->getToken($data);
        if ($result['state']) {
            $data['access_token'] = $result['data']['access_token'];
            $data['refresh_token'] = $result['data']['refresh_token'];
            $data['token_expire_time'] = time()+$result['data']['expires_in'];
            $data['refresh_expire_time'] = time()+$result['data']['refresh_expires_in'];
            $data['is_authorization'] = 1;
        } else {
            throw new JsonErrorException($result['message'], 500);
        }
        $data['update_time'] = time();
        try {
            $this->lazadaAccountModel->allowField(true)->save($data, ['id' => $id]);
            //删除缓存
            Cache::store('lazadaAccount')->delAccount();
            return date('Y-m-d', $data['refresh_expire_time']);
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }


    public function refresh_token($id) {
        $cache = Cache::store('LazadaAccount');
        $account = $cache->getAccount($id);
        if(empty($account['app_key']) || empty($account['app_secret']) || empty($account['refresh_token'])){
            return json_error('帐号授权信息不完整');
        }
        $lazadaAccountModel = new lazadaAccount();
        //检测账号的token
        $result = LazadaApi::instance($account)->loader('common')->checkToken($account, true);
        if ($result && !empty($result['data'])) {
            //更新token
            $temp['access_token'] = $result['data']['access_token'];
            $temp['refresh_token'] = $result['data']['refresh_token'];
            $temp['token_expire_time'] = time()+$result['data']['expires_in'];
            $temp['refresh_expire_time'] = time()+$result['data']['refresh_expires_in'];
            //入库
            $lazadaAccountModel->where(['id' => $account['id']])->update($temp);
            $cache->delAccount($id);
            return json(['message' => '更新成功', 'data' => $cache->getAccount($id)]);
        }else {
            return json_error('更新失败'. '('. $result['message']. ')');
        }
    }

    /** 授权页面
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function authorization($id)
    {
        $result = $this->lazadaAccountModel->field('app_key,app_secret')->where(['id' => $id])->select();
        return $result;
    }

    /**
     * @doc 查询
     * @param $keyword
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function query($keyword, $page = 1, $pageSize = 20)
    {
        $model = new lazadaAccount();
        $model->whereLike("code|name", "%$keyword%");
        $model->field('code as label, name as name, id as value');
        $data = $model->page($page, $pageSize)->select();
        $model = new lazadaAccount();
        $model->whereLike("code|name", "%$keyword%");
        $count= $model->count();
        return ['count'=>$count, 'page'=>$page, 'pageSize'=>$pageSize, 'data'=>$data];
    }


}