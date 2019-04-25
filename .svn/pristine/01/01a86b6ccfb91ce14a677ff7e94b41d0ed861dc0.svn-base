<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\wish\WishAccount;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use think\Request;
use service\wish\WishApi;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/25
 * Time: 11:17
 */
class WishAccountService
{
    protected $wishAccountModel;

    public function __construct()
    {
        if (is_null($this->wishAccountModel)) {
            $this->wishAccountModel = new WishAccount();
        }
    }

    /**
     * 账号列表
     * @param $params
     * @param array $field
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function accountList($params,array $field = [],$page = 1,$pageSize = 10)
    {
        $where = [];
        if (isset($params['status'])) {
            $params['status'] = $params['status'] == 'true' ? 1 : 0;
            $where['is_invalid'] = ['eq', $params['status']];
        }
        if (isset($params['authorization']) && $params['authorization'] > -1) {
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
                    $where['account_name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'code':
                    $where['code'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }
        if (isset($params['id'])) {
            $where['id'] = ['eq', $params['id']];
        }
        if(isset($params['taskName']) && isset($params['taskCondition']) && isset($params['taskTime']) && $params['taskName'] !== '' && $params['taskTime'] !== '') {
            $where[$params['taskName']] = [trim($params['taskCondition']), $params['taskTime']];
        }
        $orderBy = fieldSort($params);
        $orderBy .= 'create_time desc,update_time desc';
        $field = 'id,code,shop_name,download_listing,download_health,account_name,wish_enabled,is_invalid,expiry_time,is_authorization,download_order,sync_delivery,create_time,update_time';
        $count = $this->wishAccountModel->field($field)->where($where)->count();
        $accountList = $this->wishAccountModel->field($field)->where($where)->order($orderBy)->page($page, $pageSize)->select();
        $new_array = [];
        foreach ($accountList as $k => $v) {
            $temp = $v->toArray();
            $temp['expiry_time'] = !empty($temp['expiry_time']) ? date('Y-m-d', $temp['expiry_time']) : '';
            $temp['is_invalid'] = $temp['is_invalid'] == 1 ? true : false;
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
        $data['wish_enabled'] = 1;  //设置为有效
        $res = $this->wishAccountModel->where('code', $data['code'])->field('id')->find();
        if (count($res)) {
            $ret['msg'] = '账户名重复';
            $ret['code'] = 400;
            return $ret;
        }
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_wish, $data['code']);
        Db::startTrans();
        try {
            $this->wishAccountModel->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $this->wishAccountModel->id;
            //删除缓存
            Cache::store('wishAccount')->getAccount($new_id);
            if (isset($data['download_health'])) {
                (new WishAccountHealthService())->openWishHealth($new_id, $data['download_health']);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
        return $new_id;
    }

    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id)
    {
        $accountInfo = $this->wishAccountModel->field('id,shop_name,download_listing,merchant_id,code,email,account_name,download_order,download_health,sync_delivery')->where(['id' => $id])->find();
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
        if ($this->wishAccountModel->isHas($id, $data['code'], '')) {
            throw new JsonErrorException('代码或者用户名已存在', 400);
        }
        Db::startTrans();
        try {
            $data['update_time'] = time();
            unset($data['id']);
            $this->wishAccountModel->allowField(true)->save($data, ['id' => $id]);
            //开通wish服务时，新增一条list数据，如果存在，则不加
            if (isset($data['download_health'])) {
                (new WishAccountHealthService())->openWishHealth($id, $data['download_health']);
            }
            //删除缓存
            $cache = Cache::store('wishAccount');
            foreach ($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            Db::commit();
            return true;
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
        if (!$this->wishAccountModel->check(['id' => $id])) {
            throw new JsonErrorException('账号不存在', 400);
        }
        try {
            $data['update_time'] = time();
            $this->wishAccountModel->allowField(true)->save($data, ['id' => $id]);
            $cache = Cache::store('wishAccount');
            foreach ($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
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
        $result = WishApi::handler('common')->getToken($data);
        if ($result['state']) {
            $data['access_token'] = $result['data']['access_token'];
            $data['refresh_token'] = $result['data']['refresh_token'];
            $data['expiry_time'] = $result['data']['expiry_time'];
            $data['is_authorization'] = 1;
        } else {
            throw new JsonErrorException($result['message'], 500);
        }
        $data['update_time'] = time();
        try {
            $this->wishAccountModel->allowField(true)->save($data, ['id' => $id]);
            //删除缓存
            $cache = Cache::store('wishAccount');
            foreach ($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return date('Y-m-d', $data['expiry_time']);
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    public function refresh_token($id) {
        $cache = Cache::store('WishAccount');
        $account = $cache->getAccount($id);
        if(empty($account['client_id']) || empty($account['client_secret']) || empty($account['refresh_token'])){
            return json_error('帐号授权信息不完整');
        }
        $wishAccountModel = new WishAccount();
        //检测账号的token
        $result = WishApi::instance(['access_token' => $account['access_token']])->loader('common')->checkToken($account, true);
        if ($result && !empty($result['data'])) {
            //更新token
            $temp['access_token'] = $result['data']['access_token'];
            $temp['refresh_token'] = $result['data']['refresh_token'];
            $temp['expiry_time'] = $result['data']['expiry_time'];
            //入库
            $wishAccountModel->where(['id' => $account['id']])->update($temp);
            $cache = Cache::store('wishAccount');
            foreach ($temp as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
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
        $result = $this->wishAccountModel->field('client_id,client_secret,redirect_uri as redirect_url')->where(['id' => $id])->select();
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
        $model = new WishAccount();
        $model->whereLike("code|account_name", "%$keyword%");
        $model->field('code as label, account_name as name, id as value');
        $data = $model->page($page, $pageSize)->select();
        $model = new WishAccount();
        $model->whereLike("code|account_name", "%$keyword%");
        $count= $model->count();
        return ['count'=>$count, 'page'=>$page, 'pageSize'=>$pageSize, 'data'=>$data];
    }

    /**
     * 获取所有的wish账号信息
     * @param bool $field
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function accounts($field = true)
    {
        $accounts = (new WishAccount())->field($field)->where(['is_invalid' => 1])->select();
        return $accounts;
    }
}