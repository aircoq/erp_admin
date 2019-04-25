<?php
namespace app\common\cache\driver;

use think\Db;
use think\Exception;
use app\common\cache\Cache;
use app\common\traits\CacheTable;
use app\common\model\User as UserModel;

class User extends Cache
{


    const cachePrefix = 'table';

    use CacheTable;

    public function __construct()
    {
        $this->model(UserModel::class);
        parent::__construct();
    }

    /** 获取用户
     * @param int $id
     * @return mixed|static
     */
    public function getOneUser($id = 0,$field = '')
    {
        if($id > 0){
            $user =  $this->getTableRecord($id,$field);
        }else{
            $user = [];
        }
        return $user;
    }

    /** 获取用户真实姓名
     * @param int $id
     * @return mixed|static
     */
    public function getOneUserRealname($id = 0)
    {
        $realname = '';
        if($id > 0){
            $user =  $this->getOneUser($id,'realname');
            $realname = $user['realname'] ?? '';
        }
        if($id == 0){
            $realname = '系统自动';
        }
        return $realname;
    }


    /** 更新用户信息
     * @param int $id
     * @return bool
     */
    public function updateUserInfo($id = 0)
    {
        try {
            $this->delUser($id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /** 删除用户
     * @param int $id
     * @return bool
     */
    public function delete($id = 0)
    {
        try {
            $this->delUser($id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    /** 获取绑定ebay/速卖通/.../账号的客服列表
     * @param $channel_id 【渠道id】
     * @return array|bool
     */
    public function getChannelCustomer($channel_id)
    {
        try {
            if ($this->redis->exists('cache:channelCustomer')) {
                $result = json_decode($this->redis->get('cache:channelCustomer'), true);
                if ($channel_id) {
                    return isset($result[$channel_id]) ? $result[$channel_id] : [];
                } else {
                    return [];
                }
            }
            //查表
            $field = 'a.id,a.channel_id,a.account_id,a.customer_id,c.username,c.realname';
            $where['channel_id'] = ['EQ', $channel_id];
            $list = Db::table('channel_user_account_map')->alias('a')->field($field)
                ->join('user c', 'a.customer_id = c.id', 'left')->group('a.customer_id')
                ->where($where)->select();
            $result = [];
            foreach ($list as $key => $vo) {
                $data = [];
                $data['customer_id'] = $vo['customer_id'];
                $data['realname'] = $vo['username'];
                $result[$channel_id][] = $data;
            }
            $this->redis->set('cache:channelCustomer', json_encode($result));
            if ($channel_id) {
                return isset($result[$channel_id]) ? $result[$channel_id] : [];
            } else {
                return [];
            }
        } catch (Exception $e) {
            return false;
        }
    }


    /** 获取客服在某平台所管理的"账号ID"-[店铺id/account_id]
     * @param int $customer_id 【客服id】
     * @param int $channel_id 【平台id】
     * @return array|bool|mixed
     */
    public function getCustomerAccount($customer_id = 0, $channel_id = 0)
    {   
        // Cache::handler()->del('cache:channelCustomerAccount');
        try {
            if ($this->redis->exists('cache:channelCustomerAccount')) {
                $result = json_decode($this->redis->get('cache:channelCustomerAccount'), true);
                if(param($result, $channel_id.'-'.$customer_id)){ 
                    return $result[$channel_id.'-'.$customer_id];
                }
            }
            //查表
            $field = 'account_id';
            $where['channel_id'] = ['EQ', $channel_id];
            $where['customer_id'] = ['EQ', $customer_id];
            $list = Db::table('channel_user_account_map')->field($field)->where($where)->select();
            $account_ids = [];
            if ($list) {
                foreach ($list as $key => $vo) {
                    $account_ids[] = $vo['account_id'];
                }
                unset($list);
                $result[$channel_id.'-'.$customer_id] = $account_ids;
            }
            $this->redis->set('cache:channelCustomerAccount', json_encode($result));
            return $account_ids;
        } catch (Exception $e) {
            return false;
        }
    }

    /** 通过分组id获取采购员
     * @param $team_id 【分组id】
     * @return mixed
     */
    public function getDeveloperUser($team_id)
    {
        if ($this->redis->exists('hash:developerUser')) {
            if ($this->redis->hexists('hash:developerUser', $team_id)) {
                return $this->redis->hget('hash:developerUser', $team_id);
            } else {
                return 0;
            }
        }
        $developerSubModel = new DeveloperSubclassMap();
        $infoList = $developerSubModel->field(true)->select();
        foreach ($infoList as $k => $v) {
            $this->redis->hset('hash:developerUser', $v['team_id'], $v['buyer_id']);
        }
        if ($this->redis->hexists('hash:developerUser', $team_id)) {
            return $this->redis->hget('hash:developerUser', $team_id);
        } else {
            return 0;
        }
    }

    /**
     * 超级浏览器用户登录code
     * @param $userId
     * @param string $codes
     * @return bool|string
     */
    public function userCodes($userId,$codes = '')
    {
        $keys = 'cache:userCodes:'.$userId;
        if($codes){
            $this->redis->set($keys,$codes,300);
        }else{
            if ($this->redis->exists($keys)) {
                return $this->redis->get($keys);
            } else {
                return false;
            }
        }
    }

    public function getTableRecord($id = 0,$field='')
    {
        $recordData = [];
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            if ($this->isExists($key)) {
                if($field){
                    $field = explode(',',$field);
                    $info = $this->cacheObj()->hMGet($key,$field);
                }else{
                    $info = $this->cacheObj()->hGetAll($key);
                }

            } else {
                $info = $this->readTable($id);
            }
            $recordData = $info;
        } else {
            $recordData = $this->readTable($id, false);
        }
        return $recordData;
    }

    /** 读取表记录信息
     * @param $id
     * @return array|mixed
     */
    private function readTable($id = 0, $cache = true)
    {
        $newList = [];
        $where = [];
        if($id > 0){
            $where['id'] = $id;
        }
        $dataList = $this->model->field(true)->where($where)->order('id asc')->select();
        foreach ($dataList as $key => $value) {
            $value = $value->toArray();
            if ($cache) {
                $key = $this->tableRecordPrefix . $value['id'];
                foreach ($value as $k => $v) {
                    $this->setData($key, $k, $v);
                }
                $this->setTable($value['id'], $value['id']);
            }
            $newList[intval($value['id'])] = $value;
        }
        if (!empty($id)) {
            return isset($newList[$id]) ? $newList[$id] : [];
        } else {
            return $newList;
        }
    }


    /**
     * 删除账号缓存信息
     * @param int $id
     */
    public function delUser($id = 0)
    {
        $this->delTableRecord($id);
        return true;
    }


}