<?php

namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Account;
use app\common\model\AccountLog;
use app\common\model\AccountUserMap;
use app\common\model\DepartmentUserMap;
use app\common\model\Server;
use app\common\model\ServerUserMap;
use app\common\service\ChannelAccountConst;
use app\common\service\UniqueQueuer;
use app\index\validate\AccountUserValidate;
use app\order\service\OrderService;
use service\baidu\operation\Common;
use think\Db;
use think\Exception;
use app\common\model\ChannelUserAccountMap;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/23
 * Time: 14:41
 */
class AccountUserMapService
{
    protected $accountUserModel;
    protected $validate;

    public function __construct()
    {
        if (is_null($this->accountUserModel)) {
            $this->accountUserModel = new AccountUserMap();
        }
        $this->validate = new AccountUserValidate();
    }

    /**
     * 账号列表
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function map($where)
    {
        $departmentService = new Department();
        $departmentUserMapService = new DepartmentUserMapService();
        $field = 'a.id,a.account_id,u.username,u.realname,a.user_id';
        $list = $this->accountUserModel->alias('a')->field($field)->where($where)->join('user u', 'a.user_id = u.id',
            'left')->select();
        foreach ($list as $key => &$value) {
            $department = '';
            $department_ids = $departmentUserMapService->getDepartmentByUserId($value['user_id']);
            foreach ($department_ids as $k => $v) {
                $department .= $departmentService->getDepartmentNames($v) . ',';
            }
            $department = rtrim($department, ',');
            $value['department'] = $department;
            $value['department_id'] = $department_ids;
            $value['realname'] = !empty($value['realname']) ? $value['realname'] : '';
            $value['username'] = !empty($value['username']) ? $value['username'] : '';
        }
        return $list;
    }

    /**
     * 保存账号成员管理信息
     * @param $account_id
     * @param array $userList
     * @return mixed
     * @throws JsonErrorException
     */
    public function add($account_id, array $userList = [])
    {
//        if (empty($userList)) {
//            throw new JsonErrorException('用户记录不能为空', 400);
//        }
        $accountInfo = (new Account())->field('server_id')->where(['id' => $account_id])->find()->toArray();
        if (empty($accountInfo['server_id'])) {
            throw new JsonErrorException('请先绑定服务器');
        }
        $accountList = $this->accountUserModel->field('user_id')->where(['account_id' => $account_id])->select();
        $accountUserList = [];
        foreach ($accountList as $key => $value) {
            $value = $value->toArray();
            array_push($accountUserList, $value['user_id']);
        }
        //需要删除的用户
        $deleteUserList = array_diff($accountUserList, $userList);
        //新增用户
        $addUserList = array_diff($userList, $accountUserList);

        return $this->updateAccountUserMapAll($account_id, $accountInfo['server_id'], $addUserList, $deleteUserList );
    }

    /**
     * 保存账号成员管理信息  -- 平台账号成员回写
     * @param $code
     * @param $channel_id
     * @param array $userList
     * @param array $user  //操作用户
     * @param bool $hasDel //是否删除旧数据
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function relation($code, $channel_id, array $userList,$user = [],$hasDel = false)
    {
        throw new Exception('旧功能已经关闭', 400);
        if (empty($userList)) {
//            throw new Exception('用户记录不能为空', 400);
        }
        //如果是亚马逊账号
        if ($channel_id == ChannelAccountConst::channel_amazon) {
            $site = substr($code, -2);
            $site = $this->amazonSiteRule($site);
            $account = substr($code, 0, -2);
            $code = $account . $site;
        }
        $accountInfo = (new Account())->field('id,server_id')->where(['account_code' => $code, 'channel_id' => $channel_id])->find();
        if (empty($accountInfo)) {
            //如果是亚马逊账号
            if ($channel_id == ChannelAccountConst::channel_amazon) {
                $site = substr($code, -2);
                if (in_array($site, ['uk', 'de', 'fr', 'it', 'es', 'us', 'ca', 'mx'])) {
                    $account = substr($code, 0, -2);
                    $code = $account . 'us';
                    $accountInfo = (new Account())->field('id,server_id')->where(['account_code' => $code, 'channel_id' => $channel_id])->find();
                    if (empty($accountInfo)) {
                        throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
                    }
                } else {
                    throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
                }
            } else {
                throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
            }
        }
        $accountInfo = $accountInfo->toArray();
        if (empty($accountInfo['server_id'])) {
            throw new Exception('账号简称【' . $code . '】请先绑定服务器');
        }
        $account_id = $accountInfo['id'];
        $accountList = $this->accountUserModel->field('user_id')->where(['account_id' => $account_id])->select();
        $accountUserList = [];
        foreach ($accountList as $key => $value) {
            $value = $value->toArray();
            array_push($accountUserList, $value['user_id']);
        }
        //新增用户
        $addUserList = array_diff($userList, $accountUserList);
        //删除用户
        if($hasDel){
            $deleteUserList = array_diff($accountUserList, $userList);
        }else{
            $deleteUserList = [];
        }
        Db::startTrans();
        try {

            //删除用户
            if($deleteUserList){
                $delWhere = [
                    'user_id' => ['in',$deleteUserList],
                    'account_id' => $account_id,

                ];
                (new AccountUserMap())->where($delWhere)->delete();
            }


            //新增用户
            foreach ($addUserList as $key => $value) {
                $temp['account_id'] = $account_id;
                $temp['user_id'] = $value;
                (new AccountUserMap())->allowField(true)->isUpdate(false)->save($temp);
            }
//            (new UniqueQueuer(WriteBackServerAccount::class))->push(
//                [
//                    'server_id' => $accountInfo['server_id'],
//                    'add' => $addUserList,
//                    'delete' => $deleteUserList
//                ]
//            );
            (new ManagerServer())->setAuthorization($accountInfo['server_id'], $addUserList, $deleteUserList ,$user);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取基础账号信息
     * @param $code
     * @param $channel_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAccountInfo($code, $channel_id)
    {
        //如果是亚马逊账号
        if ($channel_id == ChannelAccountConst::channel_amazon) {
            $site = substr($code, -2);
            $site = $this->amazonSiteRule($site);
            $account = substr($code, 0, -2);
            $code = $account . $site;
        }
        $field = 'id,server_id,site_code,status';
        $accountInfo = (new Account())->field($field)->where(['account_code' => $code, 'channel_id' => $channel_id])->find();
        if (empty($accountInfo)) {
            switch ($channel_id){
                case ChannelAccountConst::channel_amazon:
                    $site = substr($code, -2);
                    if (in_array($site, ['uk', 'de', 'fr', 'it', 'es', 'us', 'ca', 'mx', 'jp'])) {
                        $account = substr($code, 0, -2);
                        $code = $account . 'us';
                        $accountInfo = (new Account())->field($field)->where(['account_code' => $code, 'channel_id' => $channel_id])->find();
                        if (empty($accountInfo)) {
                            $code = $account . 'uk';
                            $accountInfo = (new Account())->field($field)->where(['account_code' => $code, 'channel_id' => $channel_id])->find();
                            if (empty($accountInfo)) {
                                throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
                            }
                        }
                    } else {
                        throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
                    }
                    break;
                case ChannelAccountConst::channel_Lazada:
                case ChannelAccountConst::channel_Shopee:
                case ChannelAccountConst::channel_Daraz:
                    $site = strtoupper(substr($code, -2));
                    $code = substr($code, 0, -2);
                    $accountInfo = (new Account())->field($field)->where(['account_code' => $code, 'channel_id' => $channel_id])->find();

                    if(empty($accountInfo)){
                        throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
                    }
                    $allsite = strtoupper($accountInfo['site_code']);
                    $allsite = explode(',',$allsite);
                    if($allsite && !in_array($site,$allsite)){
                        throw new Exception('账号基础资料这个平台简称【' . $code . '】.不存在该站点'.$site);
                    }
                    break;
                case ChannelAccountConst::channel_Joom:
                    $code = explode('/',$code);
                    $code = $code[0];
                    $accountInfo = (new Account())->field($field)->where(['account_code' => $code, 'channel_id' => $channel_id])->find();
                    if(empty($accountInfo)){
                        throw new Exception('账号基础资料不存在这个平台账号简称【' . $code . '】');
                    }
                    break;
                default:
                    throw new Exception('账号基础资料不存在这个平台简称【' . $code . '】');
            }
        }
        $accountInfo = $accountInfo->toArray();
        if (empty($accountInfo['server_id'])) {
            throw new Exception('账号简称【' . $code . '】请先绑定服务器');
        }
        if ($accountInfo['status'] == Account::status_cancellation) {
            throw new Exception('账号简称【' . $code . '】的状态为已作废，不能添加');
        }
       
        return $accountInfo;
    }

    /**
     * 保存账号成员管理信息  -- 平台账号成员回写
     * @param $code
     * @param $channel_id
     * @param array $userList
     * @param array $user  //操作用户
     * @param bool $hasDel //是否删除旧数据
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function relationNew($code, $channel_id, array $userList,$delUserId,$user = [])
    {
        $accountInfo = $this->getAccountInfo($code, $channel_id);
        $account_id = $accountInfo['id'];
        $accountList = $this->accountUserModel->field('user_id')->where(['account_id' => $account_id])->select();
        $accountUserList = [];
        foreach ($accountList as $key => $value) {
            $value = $value->toArray();
            array_push($accountUserList, $value['user_id']);
        }
        //新增用户
        $addUserList = array_diff($userList, $accountUserList);
        //删除用户
        $deleteUserList = array_diff($delUserId, $userList);

        return $this->updateAccountUserMapAll($account_id, $accountInfo['server_id'], $addUserList, $deleteUserList,$user);
    }

    /**
     * 账号信息回写
     * @param array $info
     * @throws Exception
     */
    public function writeBack(array $info,$user = [])
    {
        try {
            $code = (new OrderService())->getAccountName($info['channel_id'], $info['account_id']);
            $userList = [];
            if (!empty($info['customer_id'])) {
                $userList = (new User())->getSuperiorInfo($info['customer_id']);
            }
            if (!empty($info['seller_id'])) {
                $superior = (new User())->getSuperiorInfo($info['seller_id']);
                $userList = array_merge($userList, $superior);
            }
            $userList = array_unique($userList);
            sort($userList);
            $this->relation($code, $info['channel_id'], $userList ,$user);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 销售人员更换部门回写
     * @param $userId
     * @param bool $isAdd
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeUserDepartment($userId,$isAdd = true)
    {
        $userInfo = Cache::store('User')->getOneUser($userId);
        if(!$userInfo){
            throw new JsonErrorException('获取用户缓存失败');
        }
        if($userInfo['job'] != 'sales'){
            return false;
        }
        $user = \app\common\service\Common::getUserInfo();
        $user['realname'] = '[更换部门]' . $user['realname'];
        $info = [
            'channel_id' => 0,
            'account_id' => 0,
            'addIds' => [],
            'delIds' => [],
            'user' => $user,
        ];
        if($isAdd){
            $info['addIds'] = [$userId];
        }else{
            $info['delIds'] = [$userId];
        }
        //1.找出该用户绑定了的账号
        $list = (new \app\common\model\ChannelUserAccountMap())->field('seller_id,channel_id,account_id')->where('seller_id', $userId)->select();
        //2.修改
        foreach ($list as $data) {
            try{
                $info['channel_id'] = $data['channel_id'];
                $info['account_id'] = $data['account_id'];
                $this->writeBackNew($info,$userId);
            }catch (\Exception $e){
                Cache::handler()->hSet('hash:AccountUserMap:error:' . date('Y-m-d', time()), date('H', time()), json_encode(['data' => $data,'error' => $e->getMessage()],JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * 账号信息回写[新方案]
     * @param array $info
     * @throws Exception
     */
    public function writeBackNew(array $info, $addMe = true)
    {
        try {
            $allChannel = [1,2,3,4,5,6,7,8,9,
                            10,11,12,13,14,15,16,17,18,19,
                            20,21,22,23,
                            32,
                ];
            //过滤掉
            if(!in_array($info['channel_id'], $allChannel)){
                  return true;
            }
            $code = (new OrderService())->getAccountName($info['channel_id'], $info['account_id']);
            $userList = [];
            foreach ($info['addIds'] as $userId){
                if($userId){
                    $superior = (new User())->getSuperiorInfo($userId, $addMe);
                    $userList = array_merge($userList, $superior);
                }
            }
            $userList = array_unique($userList);
            $delUserId = [];
            foreach ($info['delIds'] as $userId){
                if($userId){
                    $superior = (new User())->getSuperiorInfo($userId, $addMe);
                    $delUserId = array_merge($delUserId, $superior);
                }
            }
            $delUserId = array_unique($delUserId);
            $this->relationNew($code, $info['channel_id'], $userList ,$delUserId,$info['user']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 亚马逊站点匹配
     * @param $site
     * @return string
     */
    public function amazonSiteRule($site)
    {
        $site = strtolower($site);
        if (in_array($site, ['uk', 'de', 'fr', 'it', 'es'])) {
            return 'uk';
        }
        if (in_array($site, ['us', 'ca', 'mx'])) {
            return 'us';
        }
        return $site;
    }

    /**
     * 移除已绑定的基础账号成员信息
     * @param $userId
     * @return bool
     * @throws Exception
     */
    public function delAccountUserMapByUserId($userId,$user = [])
    {
        //1. 查出绑定了的平台ID
        $accountIds = (new AccountUserMap())->where('user_id', $userId)->column('account_id');

        //2.查询平台是的数据信息
        $where['id'] = ['in', $accountIds];
        $accountList = (new Account())->where($where)->column('server_id', 'id');

        //3.删除 资料成员信息
        foreach ($accountList as $id => $server_id) {
            $this->updateAccountUserMapAll($id, $server_id, [], [$userId],$user);
        }

        //4.删除负责人信息
        $whereMap = [
            'is_leader' => 1,
            'user_id' => $userId,
        ];
        (new DepartmentUserMap())->where($whereMap)->delete();

        return true;
    }

    /**
     * 批量操作某个平台账号资料的成员
     * @param $channel_id
     * @param $user_id
     * @param $is_add  是否添加，true 是，false 删除
     * @return string
     */
    public function batchSetAccountUsers($channel_id, $user_id, $is_add = true,$user = [])
    {
        //1.查询平台是的数据信息
        $where['channel_id'] = $channel_id;
        $where['status'] = 3;
        $accountList = (new Account())->where($where)->column('server_id', 'id');

        //2.更新
        foreach ($accountList as $id => $server_id) {
            $this->updateAccountUserMap($id, $user_id, $server_id, $is_add,$user);
        }
        return true;
    }

    /**
     * 更新账号资料的成员
     * @param $id
     * @param $user_id
     * @param $server_id
     * @param $is_add
     * @return bool
     * @throws Exception
     */
    public function updateAccountUserMap($id, $user_id, $server_id, $is_add,$user = [])
    {

        $temp['account_id'] = $id;
        $temp['user_id'] = $user_id;
        $isHasUserId = $this->accountUserModel->where($temp)->value('user_id'); //以前的表中是否存在
        Db::startTrans();
        try {
            if ($is_add && !$isHasUserId) { //新增用户
                (new AccountUserMap())->allowField(true)->isUpdate(false)->save($temp);
                (new ManagerServer())->setAuthorization($server_id, [$user_id],[],$user);
                AccountLog::addLog($id,AccountLog::user,[$user_id],[],'',$user);
            }
            if (!$is_add && $isHasUserId) { // 删除用户
                (new AccountUserMap())->where($temp)->delete();
                $delUser = [$user_id];
                $this->checkDelUser($delUser, $id, $server_id);
                (new ManagerServer())->setAuthorization($server_id, [], $delUser,$user);
                AccountLog::addLog($id,AccountLog::user,[],[$user_id],'',$user);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            Cache::handler()->hSet('hash:AccountUserMap:error:' . date('Y-m-d', time()), date('H', time()), json_encode(['error' => $e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
        return true;
    }


    /**
     * 更新账号资料的成员[批量，队列]
     * @param $id
     * @param $user_id
     * @param $server_id
     * @param $is_add
     * @return bool
     * @throws Exception
     */
    public function updateAccountUserMapAll($id, $server_id, $addUser = [], $delUser = [],$user = [])
    {

        $tempAdd = [];
        $model = new AccountUserMap();
        //再次查找是否存在
        foreach ($addUser as $v){
            $where = [
                'account_id' => $id,
                'user_id' => $v,
            ];
            $isHas = $model->where($where)->value('id');
            if(!$isHas){
                $tempAdd[] =$where;
            }
        }
        Db::startTrans();
        try {
            if ($tempAdd) { //新增用户
                (new AccountUserMap())->allowField(true)->isUpdate(false)->saveAll($tempAdd);
            }
            if ($delUser) { // 删除用户
                $temp['account_id'] = $id;
                $temp['user_id'] = ['in', $delUser];
                (new AccountUserMap())->where($temp)->delete();
            }
            AccountLog::addLog($id,AccountLog::user,$addUser,$delUser,'',$user);
            $this->checkDelUser($delUser, $id, $server_id);
            (new ManagerServer())->setAuthorizationAll($server_id, $addUser,$delUser ,$user);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Cache::handler()->hSet('hash:AccountUserMapAll:error:' . date('Y-m-d', time()), date('H', time()), json_encode(['error' => $e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    /**
     * 移除掉不能删除的用户
     * @param $delUser
     * @param $id
     * @param $serverId
     * @return bool
     */
    public function checkDelUser(&$delUser,$id,$serverId)
    {
        $whereAccount = [
            'server_id' => $serverId,
            'id' => ['<>', $id],
        ];
        $accountIds = (new Account())->where($whereAccount)->column('id');
        if(!$accountIds){
            return true;
        }
        $model = new AccountUserMap();
        $where = [
            'account_id' => ['in', $accountIds],
        ];
        foreach ($delUser as $k => $userId){
            $where['user_id'] = $userId;
            $isHas = $model->where($where)->value('id');
            if($isHas){
                unset($delUser[$k]);
            }
        }
    }

    /**
     * 测试批量操作某个平台账号资料的成员
     */
    public function testAdd()
    {
//        速卖通：陈健清、郑竣、曾刚
//        亚马逊：曹玲、熊佳、郑竣、曾刚
//        eBay：曹玲、熊佳、郑竣、曾刚
        $data = [
            4 => [1172, 2274, 2237],
            2 => [1698, 1111, 2274, 2237],
            1 => [1698, 1111, 2274, 2237],
        ];
        foreach ($data as $k => $v) {
            foreach ($v as $item) {
                $temp = [
                    'channel_id' => $k,
                    'user_id' => $item,
                    'is_add' => true,
                ];
                $service = new UniqueQueuer(\app\index\queue\AccountUserMapBatchQueue::class);
                $service->push($temp);
            }
        }
        return true;
    }

    /**
     * 查找某个用户绑定的账号基础资料的IDs
     * @param $userId
     * @return array
     */
    public function userBingdingAccountIds($userId)
    {
        $accounts = $this->accountUserModel->where('user_id',$userId)->column('account_id');
        return $accounts ? $accounts : [];
    }

    /**
     * 批量更新不對的數據
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateUserMap()
    {
        $user = \app\common\service\Common::getUserInfo();
        $str = '434,401,2381,2696,3905,4791,4790,3844,3839,3792,4681,3787,3785,3763,3760,3759,3758,3755,3746,3779,3775,4085,2679,2800,2789,4052,4684,4049,4228,3994,3992,3991,2691,2682,2694,2680,2147,3455,4190,3048,3612,2645,2955,2136,2974,2975,2623,2973,2164,2646,2674';
        $accountIDs = explode(',',$str);
        $server = (new Account())->where('id','in',$accountIDs)->column('server_id','id');
        foreach ($server as $id => $serverid){
            $accountUser = (new AccountUserMap())->where('account_id',$id)->column('user_id');
            $serverUser = (new ServerUserMap())->where('server_id',$serverid)->column('user_id');
            $addUserIds = array_diff($accountUser,$serverUser);
            foreach ($addUserIds as $user_id){
                try{
                    (new ManagerServer())->setAuthorization($serverid, [$user_id],[],$user);
                }catch (\Exception $ex){
                    Cache::handler()->hSet('hash:test:updateUserMap',$serverid.'_'.$user_id,$ex->getMessage());
                }

            }
        }
    }

    /**
     * 用户是否已经绑定平台
     * @param $channel_id 平台id
     * @param array $ids 用户id
     * @return array
     * @throws Exception
     */
    public function bindAccountIds($channel_id, array $ids)
    {
        $where = [
            'channel_id' => ['eq', $channel_id],
            'account_id' => ['in', $ids]
        ];

       return (new ChannelUserAccountMap())->where($where)->column('account_id');
    }

    /**
     * 获取joom平台下已经绑定的商铺
     * @return array
     * @throws Exception
     */
    public function getJoomShopIds()
    {
        $where = [
            'channel_id' => ChannelAccountConst::channel_Joom
        ];

        return (new ChannelUserAccountMap())->where($where)->column('account_id');
    }
}