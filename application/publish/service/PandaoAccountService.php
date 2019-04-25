<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-24
 * Time: 上午11:03
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\pandao\PandaoAccount;
use app\common\service\ChannelAccountConst;
use app\publish\validate\PandaoValidate;
use erp\AbsServer;
use service\pandao\operation\Account;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class PandaoAccountService
{
    /**
     * 刷新token
     * @param $params
     * @return array
     * @throws Exception
     */
    public static function refressAccessToken($params){
        if(!isset($params['id']) || empty($params['id'])){
            throw new Exception("账号id为空");
        }
        $id = $params['id'];
        $response = (new Account())->refreshToken($params);
        if(isset($response['access_token']) && isset($response['refresh_token']))
        {
            $response['expiry_time'] = time()  + $response['expires_in'];
            $params= array_merge($params,$response);
            $params['enabled']=$params['is_invalid']=$params['is_authorization']=1;
            $where['id']=['=',$id];
            $model = new PandaoAccount();
            Db::startTrans();
            try{
                $model->allowField(true)->isUpdate(true)->save($params,['id'=>$id]);
                Db::commit();
                $cache = Cache::store('PandaoAccountCache');
                foreach($params as $key=>$val) {
                    $cache->updateTableRecord($id, $key, $val);
                }
                return ['message'=>'授权成功'];
            }catch (PDOException $exp){
                Db::rollback();
                throw new Exception($exp->getMessage());
            }
        }elseif(isset($response['error_description']) && $response['error_description']=='Invalid refresh token'){
            $params['username']=$params['account_name'];
            (new self())->authorization($params);
        }
    }

    /**
     * 授权
     * @param $params
     * @param $uid
     * @return array
     * @throws Exception
     */
    public function authorization($params,$uid=1){
        $validate = new PandaoValidate();
        if($error = $validate->checkData($params,'authorization'))
        {
            throw new JsonErrorException($error);
        }
        $id = $params['id'];
        $response = (new Account())->authorization($params);
        if(isset($response['access_token']) && isset($response['refresh_token']))
        {
            $response['expiry_time'] = time()  + $response['expires_in'];
            $params= array_merge($params,$response);
            $params['enabled']=$params['is_invalid']=$params['is_authorization']=1;
            $params['updater_id']=$uid;
            $where['id']=['=',$id];
            $model = new PandaoAccount();
            Db::startTrans();
            try{
                $model->allowField(true)->isUpdate(true)->save($params,['id'=>$id]);
                Db::commit();
                $cache = Cache::store('PandaoAccountCache');
                foreach($params as $key=>$val) {
                    $cache->updateTableRecord($id, $key, $val);
                }
                return ['message'=>'授权成功'];
            }catch (PDOException $exp){
                Db::rollback();
                throw new Exception($exp->getMessage());
            }
        }else{
            throw new Exception("授权失败:".$response['error_description']);
        }
    }
    /**
     * 更新账号启用状态
     * @param $params
     * @throws Exception
     */
    public function changeStatus($params){
        $validate =  new PandaoValidate();
        if($error = $validate->checkData($params,'change_status'))
        {
            throw new JsonErrorException($error);
        }

        if($params['is_invalid']==1){
            $message='启用成功';
        }else{
            $message='停用成功';
        }

        $id = $params['id'];
        Db::startTrans();
        try{
            (new PandaoAccount())->allowField(true)->save($params,['id'=>$id]);
            Db::commit();
            $cache = Cache::store('PandaoAccountCache');
            foreach($params as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return ['message'=>$message];
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * 获取一条记录
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOne($id){
        return (new PandaoAccount())->where('id',$id)->find();
    }

    /**
     * 更新账号
     * @param $params
     * @param $uid
     * @return array
     */
    public function update($params,$uid){
        $validate =  new PandaoValidate();
        if($error = $validate->checkData($params,'update_account'))
        {
            throw new JsonErrorException($error);
        }
        $id = $params['id'];
        Db::startTrans();
        try {
            $model = new PandaoAccount();
            $params['creator_id']=$uid;
            $model->allowField(true)->isUpdate(true)->save($params,['id'=>$id]);
            Db::commit();
            //新增缓存

            //更新缓存
            $cache = Cache::store('PandaoAccountCache');
            foreach($params as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            $data = $cache->getAccountById($id);
            foreach ($data as $key=>$value){
                if($key=='enabled' || $key=='is_authorization' || $key=='is_invalid'){
                    $data[$key]=$value?1:0;
                }elseif($key=='expiry_time'){
                    $data[$key]=$value?$value:'';
                }
            }
            return ['message' =>  '更新成功','data'=>$data];
        } catch (Exception $exp) {
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * 添加账号
     * @param $params
     * @param $uid
     * @return array
     */
    public function add($params,$uid){
        $validate =  new PandaoValidate();
        if($error = $validate->checkData($params,'add_account'))
        {
              throw new JsonErrorException($error);
        }
        $where['account_name']=['=',$params['account_name']];
        //必须要去账号基础资料里备案
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Pandao,$params['code']);
        Db::startTrans();
        try {
            $model = new PandaoAccount();
            if($has = $model->where($where)->field('id')->find()){
                $params['updater_id']=$uid;
                $model->allowField(true)->isUpdate(true)->save($params,['id'=>$has['id']]);
                $id = $has['id'];
                $message='更新成功';
            }else{
                $params['creator_id']=$uid;
                $model->allowField(true)->isUpdate(false)->save($params);
                $id = $model->id;
                $message='新增成功';
            }
            Db::commit();
            //新增缓存
            //Cache::store('PandaoAccountCache')->setTableRecord($id);
            //更新缓存
            $cache = Cache::store('PandaoAccountCache');
            foreach($params as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            $data = $cache->getAccountById($id);
            foreach ($data as $key=>$value){
                if($key=='enabled' || $key=='is_authorization' || $key=='is_invalid'){
                    $data[$key]=$value?1:0;
                }elseif($key=='expiry_time'){
                    $data[$key]=$value?$value:'';
                }
            }
            //$data = $model->where('id',$id)->find();
            return ['message' =>  $message,'data'=>$data];
        } catch (Exception $exp) {
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * 账号列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws Exception
     */
    public function lists($params,$page=1,$pageSize=30){
        try{
            $where = [];
            if (isset($params['is_invalid']) && is_numeric($params['is_invalid'])) {
                $where['is_invalid'] = ['=', $params['is_invalid']];
            }
            if (isset($params['is_authorization']) && is_numeric($params['is_authorization'])) {
                $where['is_authorization'] = ['=', $params['is_authorization']];
            }
            if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
                switch ($params['snType']) {
                    case 'account_name':
                        $where['account_name'] = [ '=', $params['snText']];
                        break;
                    case 'code':
                        $where['code'] = [ '=', $params['snText']];
                        break;
                    default:
                        break;
                }
            }

//            $account_list = Cache::store('PandaoAccountCache')->getTableRecord();
//
//            arsort($account_list);
//            if (isset($where)) {
//                $account_list = Cache::filter($account_list, $where);
//            }
//            //总数
//            $count = count($account_list);
//            $accountData = Cache::page($account_list, $page, $pageSize);
//            $new_array = [];
            $orderBy = fieldSort($params);
            $orderBy .= 'create_time desc,update_time desc';
            $model = new PandaoAccount();
            $count = $model->where($where)->count();
            $accountData = $model->where($where)->page($page,$pageSize)->order($orderBy)->select();
            foreach ($accountData as $k => $v) {
                $this->updateEnabled($v);
                $v['expiry_time'] = !empty($v['expiry_time']) ? date('Y-m-d H:i:s', $v['expiry_time']) : '';
                $v['is_invalid'] = (int)$v['is_invalid'];
                $v['id'] = (int)$v['id'];
                $v['is_authorization'] = (int)$v['is_authorization'];
                $new_array[$k] = $v;
            }
            $result = [
                'data' => $new_array,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
            return $result;
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * @desc 更新账号是否有效标识
     * @param array $data 速卖通账号信息
     * @author Jimmy
     * @date 2017-11-09 20:03:11
     */
    private function updateEnabled(&$data)
    {
        try {
            //授权已失效
            if ($data['expiry_time'] < time()) {
                $data['enabled'] = 0;
                //修改表
                $model = PandaoAccount::get($data['id']);
                if ($model) {
                    $model->enabled = 0;
                    $model->save();
                    //更新缓存
                    $cache = Cache::store('PandaoAccountCache');
                    foreach($data as $key=>$val) {
                        $cache->updateTableRecord($data['id'], $key, $val);
                    }
                }
            }
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }
    }
}