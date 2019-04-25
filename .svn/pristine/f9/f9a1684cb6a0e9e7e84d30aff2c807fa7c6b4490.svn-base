<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\umka\UmkaAccount;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use think\Request;
use umka\UmkaAccountApi;
use think\Db;


/**
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2018/9/11
 * Time: 10:00
 */
class UmkaAccountService
{
    protected $umkaAccountModel;
    protected $error = '';
    public function __construct()
    {
        if (is_null($this->umkaAccountModel)) {
            $this->umkaAccountModel = new UmkaAccount();
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

    /** 账号列表
     * @param Request $request
     * @return array
     * @throws \think\Exception
     */
    public function accountList(Request $request)
    {
        $where = [];
        $params = $request->param();

        if (isset($params['status']) && $params['status'] != '' ) {
           $params['status'] = $params['status'] == 'true' ? 1 : 0;
            $where['is_invalid'] = ['eq', $params['status']];
        }
        if (isset($params['authorization']) && $params['authorization']!='' && $params['authorization']!=-1) {
            $params['authorization'] = $params['authorization'] == 1? 1 : 0;
            $where['is_authorization'] = ['eq', $params['authorization']];
        }
        if (isset($params['download_order']) && $params['download_order'] > -1) {
            if(empty($params['download_order'])){
                $where['download_order'] = ['eq', 0];
            }else{
                $where['download_order'] = ['>', 0];
            }
        }
        if (isset($params['sync_listing']) && $params['sync_listing'] > -1) {
            if(empty($params['sync_listing'])){
                $where['sync_listing'] = ['eq', 0];
            }else{
                $where['sync_listing'] = ['>', 0];
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
                case 'name':
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
        $field = 'id,code,name,shop_name,email,enabled,is_invalid,download_order,sync_delivery,client_id,client_secret,create_time,creator_id,update_time,updater_id,refresh_token,access_token,expiry_time,is_authorization,sync_listing,password';
        $count = $this->umkaAccountModel->field($field)->where($where)->count();
        $accountList = $this->umkaAccountModel->field($field)->where($where)->order($orderBy)->page($page, $pageSize)->select();
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
            $save_data['download_order'] = $data['download_order'] ?? 0;
            $save_data['sync_listing'] = $data['sync_listing'] ?? 0;
            $save_data['sync_delivery'] = $data['sync_delivery'] ?? 0;
            $save_data['base_account_id'] = $data['base_account_id'] ?? 0;
            if ($id == 0){
                //检查产品是否已存在
                if ($this->umkaAccountModel->check(['name' => $data['name']])) {
                    $this->error =  $data['name'].'账号已经存在无法重复添加';
                    return false;
                }
                if ($this->umkaAccountModel->check(['code' => $data['code']])) {
                    $this->error = $data['code'].'简称已经存在无法重复添加';
                    return false;
                }
                //必须要去账号基础资料里备案
                \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::Channel_umka,$data['code']);
                $save_data['name'] = $data['name'];
                $save_data['create_time'] = $time;
                $save_data['creator_id'] = $uid;
            } else{
                $is_ok = $this->umkaAccountModel->field('id')->where(['code' => $data['code']])->where('id','<>',$id)->find();
                if($is_ok){
                    $this->error = 'Umka简称已经存在无法修改';
                    return false;
                }
                $save_data['id'] = $id;
                $save_data['updater_id'] = $uid;
                //更新缓存
                $cache = Cache::store('UmkaAccount');
                foreach ($save_data as $key => $val) {
                    $cache->updateTableRecord($id, $key, $val);
                }
            }
           $this->umkaAccountModel->add($save_data);
           return $this->read($id);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }


    /** 更新
     * @param $id
     * @param $data
     * @return \think\response\Json
     */
    public function update($id, $data)
    {
        if ($this->umkaAccountModel->isHas($id, $data['code'], '')) {
            throw new JsonErrorException('代码或者用户名已存在', 400);
        }
        $model = $this->umkaAccountModel->get($id);
        Db::startTrans();
        try {
            //赋值
            $model->code = isset($data['code'])?$data['code']:'';
            $model->name = isset($data['name'])?$data['name']:'';
            $model->client_id = isset($data['client_id'])?$data['client_id']:'';
            $model->download_order = isset($data['download_order'])?$data['download_order']:'';
            $model->download_listing = isset($data['sync_listing'])?$data['sync_listing']:'';
            $model->sync_delivery = isset($data['sync_delivery'])?$data['sync_delivery']:'';
            $model->update_time =time();
            unset($data['id']);
            //插入数据
            $model->allowField(true)->isUpdate(true)->save();
            //删除缓存
            Cache::store('umkaAccount')->delAccount();
            Db::commit();
            return $model;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }


    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id)
    {
        $field = 'id,code,name,client_id,updater_id,client_secret,create_time,update_time,creator_id,email,enabled,is_authorization,download_order,sync_delivery,sync_listing,client_secret,access_token,expiry_time,is_invalid,password,refresh_token,shop_name';
        if ($id == 0) {
            return $this->umkaAccountModel->field($field)->order('id desc')->find();
        }
        return $this->umkaAccountModel->where('id', $id)->field($field)->find();
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
            $this->umkaAccountModel->allowField(true)->update($param,['id' => ['in', json_decode($ids)]]);
            $model = $this->umkaAccountModel->field('id ,status,download_order,download_listing,sync_delivery')->where(['id' => ['in', json_decode($ids)]])->select();
            unset($ids);
            //删除缓存
            Cache::store('pddAccount')->delAccount();
            Db::commit();
            return $model;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /** 状态
     * @param $data
     * @return array
     */
    public function changeStatus($data)
    {

        $cache = Cache::store('UmkaAccount');
        $account = $cache->getAccount($data['id']);
        if (!isset($account)) {
            $this->error = '账号不存在';
            return false;
        }
        try {
            $updata = [];
            if (isset($data['is_invalid'])) {
                $updata['is_invalid'] = $data['is_invalid'];
            }
            $updata['update_time'] = time();
            $this->umkaAccountModel->allowField(true)->save($updata, ['id' => $data['id']]);
            //修改缓存
            $cache = Cache::store('UmkaAccount');
            foreach ($updata as $key => $val) {
                $cache->updateTableRecord($data['id'], $key, $val);
            }
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
        $ret = [
            'msg' => '',
            'code' => '',
        ];
        $umka    = new UmkaAccountApi();
        $result= $umka->get_access_token($data);
        $expires_in=0;
        if(isset($result['access_token']) && $result['access_token']){
            $expires_in=$result['expires_in']??0;
            $data['access_token'] = $result['access_token'];
            $data['refresh_token'] = $result['refresh_token'];
            $data['expiry_time'] =time()+intval($expires_in);
            $data['token_type']=$result['token_type'];
            $data['scope']=$result['scope'];
            $data['is_authorization'] = 1;
            $data['update_time'] = time();
        }else{
            $ret['msg'] = '获取授权失败 ' . param($result, 'error', '');
            $ret['code'] = 400;
            return $ret;
        }
        try {
            $this->umkaAccountModel->allowField(true)->save($data, ['id' => $id]);
            //删除缓存
            Cache::store('umkaAccount')->delAccount();
            $ret = [
                'msg' => date('Y-m-d', time()+intval($expires_in)),
                'code' => 200,
            ];
            return $ret;
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }


    public function refresh_token($id) {
        $cache = Cache::store('UmkaAccount');
        $account = $cache->getAccount($id);
        if(empty($account['client_id']) || empty($account['client_secret']) || empty($account['refresh_token'])){
            return json_error('帐号授权信息不完整');
        }
        $umkaAccountModel =new umkaAccount();
        //检测账号的token
        $umka    = new UmkaAccountApi();
        $result = $umka->refreshToken($account);
        if ($result) {
            //更新token
            $temp['access_token'] = $result['access_token'];
            $temp['refresh_token'] = $result['refresh_token'];
            $temp['expiry_time'] = $result['expires_in'];
            //入库
            $umkaAccountModel->where(['id' => $account['id']])->update($temp);
            $cache->delAccount($id);
            return json(['message' => '更新成功', 'data' => $cache->getAccount($id)]);
        }else {
            return json_error('更新失败');
        }
    }

    /** 授权页面
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function authorization($id)
    {

        $result = $this->umkaAccountModel->field('client_id','client_secret')->where(['id' => $id])->select();

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
        $model = new umkaAccount();
        $model->whereLike("code|name", "%$keyword%");
        $model->field('code as label, name as name, id as value');
        $data = $model->page($page, $pageSize)->select();
        $model = new umkaAccount();
        $model->whereLike("code|name", "%$keyword%");
        $count= $model->count();
        return ['count'=>$count, 'page'=>$page, 'pageSize'=>$pageSize, 'data'=>$data];
    }


}