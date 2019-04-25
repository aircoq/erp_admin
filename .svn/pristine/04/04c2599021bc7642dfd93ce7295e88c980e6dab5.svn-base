<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\joom\JoomShop as JoomShopModel;
use app\common\model\joom\JoomAccount as JoomAccountModel;
use app\common\cache\Cache;

use joom\JoomAccountApi;
use think\Request;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/25
 * Time: 11:17
 */
class JoomShopService
{
    protected $joomShopModel;

    public $error = '';

    public function __construct()
    {
        if (is_null($this->joomShopModel)) {
            $this->joomShopModel = new JoomShopModel();
        }
    }

    public function getError() {
        return $this->error;
    }

    /** 账号列表
     * @param Request $request
     * @return array
     * @throws \think\Exception
     */
    public function shopList(Request $request)
    {
        $where = [];
        $params = $request->param();
        if (isset($params['status']) && ($params['status'] !== '')) {
            $params['status'] = $params['status'] == 'true' ? 1 : 0;
            $where['joom_shop.is_invalid'] = ['eq', $params['status']];
        }
        if (isset($params['authorization']) && ($params['authorization'] !== '')) {
            $where['joom_shop.is_authorization'] = ['eq', $params['authorization']];
        }

        if (isset($params['joom_enabled']) && ($params['joom_enabled'] !== '')) {
            $where['joom_shop.joom_enabled'] = ['eq', $params['joom_enabled']];
        }

        if (isset($params['download_order']) && $params['download_order'] > -1) {
            if(empty($params['download_order'])){
                $where['joom_shop.download_order'] = ['eq', 0];
            }else{
                $where['joom_shop.download_order'] = ['>', 0];
            }
        }
        if (isset($params['download_listing']) && $params['download_listing'] > -1) {
            if(empty($params['download_listing'])){
                $where['joom_shop.download_listing'] = ['eq', 0];
            }else{
                $where['joom_shop.download_listing'] = ['>', 0];
            }
        }
        if (isset($params['sync_delivery']) && $params['sync_delivery'] > -1) {
            if(empty($params['sync_delivery'])){
                $where['joom_shop.sync_delivery'] = ['eq', 0];
            }else{
                $where['joom_shop.sync_delivery'] = ['>', 0];
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'shop_name':
                    $where['joom_shop.shop_name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'code':
                    $where['joom_shop.code'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'account_name':
                    $where['joom_account.account_name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'account_code':
                    $where['joom_account.code'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }
        if(isset($params['taskName']) && isset($params['taskCondition']) && isset($params['taskTime']) && $params['taskName'] !== '' && $params['taskTime'] !== '') {
            $where[$params['taskName']] = [trim($params['taskCondition']), $params['taskTime']];
        }

        $order = 'joom_shop.id';
        $sort = 'desc';
        $sortArr = ['shop_name' => 'joom_shop.shop_name', 'shop_code' => 'joom_shop.code', 'account_name' => 'joom_account.account_name', 'account_code' => 'joom_account.code', 'expiry_time' => 'joom_shop.expiry_time'];
        if (!empty($params['order_by']) && !empty($sortArr[$params['order_by']])) {
            $order = $sortArr[$params['order_by']];
        }
        if (!empty($params['sort']) && in_array($params['sort'], ['asc', 'desc'])) {
            $sort = $params['sort'];
        }

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $field = 'id,code,shop_name,joom_account_id,merchant_id,email,joom_enabled,is_invalid,download_order,download_listing,sync_delivery,expiry_time,is_authorization,create_time,update_time';
        $count = Db::view('joom_account', 'account_name')
            ->view('joom_shop', 'id', 'joom_account.id=joom_shop.joom_account_id')
            ->where($where)
            ->count();
        $shopList = Db::view('joom_account', 'account_name,code account_code')
            ->view('joom_shop', $field, 'joom_account.id=joom_shop.joom_account_id')
            ->where($where)
            ->order($order, $sort)
            ->page($page, $pageSize)
            ->select();

        $result = [
            'data' => $shopList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 获取各账户的店辅数量
     * @return array
     */
    public function accountCounts()
    {
        $shopM = new JoomShopModel();
        $list = $shopM->field('joom_account_id,count(id) as total')->group('joom_account_id')->select();
        $accountlist = Cache::filter(Cache::store('JoomAccount')->getAllAccounts(), [['is_invalid', '==', 1], ['platform_status', '==', 1]], 'id,account_name');
        $accountlist = array_combine(array_column($accountlist, 'id'), array_column($accountlist, 'account_name'));
        $result = [];
        foreach($accountlist as $key=>$val) {
            $total = 0;
            if(!empty($list)) {
                foreach($list as $shop) {
                    if($shop['joom_account_id'] == $key) {
                        $total = $shop['total'];
                        break;
                    }
                }
            }
            $result[] = [
                'label' => $val,
                'total' => $total,
                'value' => $key,
            ];
        }

        return $result;
    }

    /** 保存账号信息
     * @param $data
     * @return array
     */
    public function save($data)
    {
        $time = time();
        $data['create_time'] = $time;
        $data['update_time'] = $time;
        $data['joom_enabled'] = 1;  //设置为有效

        $accoutcheck = JoomAccountModel::where(['id' => $data['joom_account_id']])->count();
        if($accoutcheck == 0) {
            $this->error = 'jomm_account_id账户不存在';
            return false;
        }

        /*
         * 店铺数量增加，去掉50家的限制
         * wangwei 2019-3-11 10:05:22
         */
//         $accoutcheck = $this->joomShopModel->where(['joom_account_id' => $data['joom_account_id']])->count();
//         if($accoutcheck >= 50) {
//             $this->error = 'jomm账户下属商户大于等于50家';
//             return false;
//         }
        Db::startTrans();
        try {
            $this->joomShopModel->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $this->joomShopModel->id;
            //新增缓存
            Cache::store('JoomShop')->setTableRecord($new_id);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
        $shopInfo = $this->joomShopModel->field(true)->where(['id' => $new_id])->find();
        return $shopInfo;
    }

    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id)
    {
        $shopInfo = $this->joomShopModel->field('id,code,shop_name,joom_account_id,merchant_id,email,download_order,download_listing,sync_delivery')->where(['id' => $id])->find();
        if(empty($shopInfo)){
            throw new JsonErrorException('账号不存在',500);
        }
        return $shopInfo;
    }

    /** 更新
     * @param $id
     * @param $data
     * @return \think\response\Json
     */
    public function update($id, $data)
    {
        if ($this->joomShopModel->isHas($id, $data['code'], '')) {
            throw new JsonErrorException('代码或者用户名已存在', 400);
        }
        Db::startTrans();
        try {
            $data['update_time'] = time();
            unset($data['id']);
            $this->joomShopModel->allowField(true)->save($data, ['id' => $id]);
            //修改缓存
            $cache = Cache::store('joomShop');
            foreach($data as $key=>$val) {
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
        if (!$this->joomShopModel->check(['id' => $id])) {
            throw new JsonErrorException('账号不存在', 400);
        }
        try {
            $data['update_time'] = time();
            $this->joomShopModel->allowField(true)->save($data, ['id' => $id]);
            //修改缓存
            $cache = Cache::store('joomShop');
            foreach($data as $key=>$val) {
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
        $posdata = $data;
        $posdata['code'] = $data['authorization_code'];
        $joomApi = new JoomAccountApi($posdata);
        $result = $joomApi->get_access_token();
        if ($result['code'] == 0) {
            $data['access_token'] = $result['data']['access_token'];
            $data['refresh_token'] = $result['data']['refresh_token'];
            $data['expiry_time'] = $result['data']['expiry_time'];
            $data['is_authorization'] = 1;
        } else {
            throw new JsonErrorException($result['message'], 500);
        }
        $data['update_time'] = time();
        try {
            $this->joomShopModel->allowField(true)->save($data, ['id' => $id]);
            //修改缓存
            $cache = Cache::store('joomShop');
            foreach($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return date('Y-m-d', $data['expiry_time']);
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /** 授权页面
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function authorization($id)
    {
        $result = $this->joomShopModel->field('client_id,client_secret,redirect_uri as redirect_url')->where(['id' => $id])->find();
        return $result;
    }

    /** 授权页面
     * @param  int $joom_account_id joom平台下账号id
     * @return array
     */
    public function getListByAccountId($joom_account_id)
    {
        $where = [
            'is_invalid' => 1,
            'joom_account_id' => ['in', $joom_account_id],
        ];
        return $this->joomShopModel->where($where)->column('id,code,shop_name,joom_account_id');
    }
}