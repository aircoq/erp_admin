<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount as AliexpressAccountModel;
use app\common\model\aliexpress\AliexpressAccountHealthPayment;
use app\common\traits\CacheTable;

/**
 * 速卖通账号缓存
 * Created by NetBeans.
 * User: Rondaful
 * Date: 2018/1/2
 * Time: 19:43
 */
class AliexpressAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:aliexpress:';
    private $orderSyncTime = 'order_sync_time';
    private $listingSyncTime = 'listing_sync_time';

    private $ralationListSyncTime = 'msg_sync_time';
    private $ralationListExecuteTime= 'msg_execute_time';

    private $lastDownloadHealthTime = 'download_health_time';

    private $evaluationSyncTime = 'evaluation_sync_time';
    private $issueSyncTime = 'issue_sync_time';
    private $healthPeyment = 'health_payment_record:';
    private $nickPrefix = 'aliexpress:user_nick:';

    use CacheTable;

    public function __construct()
    {
        $this->model(AliexpressAccountModel::class);
        parent::__construct();
    }

    /**
     * Aliexpress账号获取listing最后更新时间
     * @param int $accountId
     * @return int
     */
    public function getListingSyncTime($accountId)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = $this->persistRedis->hget($key, $accountId);
            return $arr ?? 0;
        }
        return 0;
    }

    /**
     * 设置Aliexpress账号同步listing的时间
     * @param int $account_id
     * @param int $syncTime
     * @return array|mixed
     */
    public function setListingSyncTime($account_id, $syncTime)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if (!empty($syncTime)) {
            $this->persistRedis->hset($key, $account_id, $syncTime);
        }
    }

    /** 设置health最后更新时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setDownloadHealthTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastDownloadHealthTime;
        if (!empty($time)) {
            $this->redis->hset($key, $account_id, $time);
        }
    }

    /** 获取health最后更新时间
     * @param $account_id
     * @return array|mixed
     */
    public function getDownloadHealthTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastDownloadHealthTime;
        if ($this->redis->hexists($key, $account_id)) {
            return $this->redis->hget($key, $account_id);
        }
        return 0;
    }

    /** 设置wish账号付款记录ID
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setAccountHealthPaymentRecord($account_id, $token, $id)
    {
        $key = $this->taskPrefix . $this->healthPeyment. $account_id;
        $hashkey = implode('-', $token);
        if (!empty($token)) {
            $this->redis->hset($key, $hashkey, $id);
        }
    }

    /** 获取wish账号付款记录ID
     * @param $account_id
     * @return array|mixed
     */
    public function getAccountHealthPaymentRecord($account_id, $token)
    {
        $key = $this->taskPrefix . $this->healthPeyment. $account_id;
        $hashkey = implode('-', $token);
        if ($this->redis->hexists($key, $hashkey)) {
            return $this->redis->hget($key, $hashkey);
        } else {
            $where['account_id'] = $account_id;
            $where['trading_time'] = $token[0];  //交易时间
            $where['payment_id'] = $token[1];   //交易唯一一ID
            $where['money'] = $token[2];    //交易金额
            $where['type'] = $token[3];    //交易金额
            $id = AliexpressAccountHealthPayment::where($where)->value('id');
            $id = empty($id)? 0 : $id;
            return $id;
        }
    }

    /**
     * 获取全部账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAllAccounts()
    {
        return $this->getTableRecord();
    }
    
    /**
     * 获取全部账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAccounts()
    {
        return $this->getTableRecord();
    }

    /**
     * 获取账号信息通过id
     * @param int $id
     * @return array|mixed
     */
    public function getAccountById($id)
    {
        return $this->getTableRecord($id);
    }

    /**
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        $this->delTableRecord($id);
        return true;
    }

    /**
     * @desc 获取/设置  订单抓取时间记录
     * @author wangwei
     * @date 2018-12-8 17:54:01
     * @param int $account_id 账号id
     * @param array $time 数据
     * @param bool $cover 是否覆盖更新
     */
    public function taskOrderTime($account_id, $time = [], $cover = false)
    {
        $key = $this->taskPrefix . $this->orderSyncTime;
        $result = json_decode($this->persistRedis->hget($key, $account_id), true);
        if ($time) {
            if(!$cover){
                foreach ($time as $field => $value){
                    if(is_array($value)){
                        foreach ($value as $kk=>$vv){
                            $result[$field][$kk] = $vv;
                        }
                    }else{
                        $result[$field] = $value;
                    }
                }
            }else{
                $result = $time;
            }
            $this->persistRedis->hset($key, $account_id, json_encode($result));
            return true;
        }
        return $result;
    }

    /**
     * 设置和抓取账号站内信关系列表最后抓取时间
     * 未处理-un_dealed 全部列表-aliexpressMsg
     */
    public function taskMsgTime($account_id, $time = [])
    {
        $key = $this->taskPrefix . $this->ralationListSyncTime;
        $result = json_decode($this->persistRedis->hget($key, $account_id), true);
        if ($time) {
            foreach ($time as $field => $value) {
                $result[$field] = $value;
            }
            $this->persistRedis->hset($key, $account_id, json_encode($result));
            return true;
        }
        return $result;
    }

    /**
     * 设置和抓取账号站内信关系列表最后抓取时间
     * 未处理-un_dealed 全部列表-aliexpressMsg
     */
    public function taskMsgExecuteTime($account_id, $time = 0)
    {
        $key = $this->taskPrefix . $this->ralationListExecuteTime;
        $this->persistRedis->hset($key, $account_id, $time);
        return true;
    }

    /**
     * 设置和抓取账号站内信关系列表最后抓取时间
     * 查询订单已生效的评价信息-listOrderEvaluation 查询待卖家评价的订单信息-evaluationOrderList
     */
    public function taskEvaluationTime($account_id, $time = [])
    {
        $key = $this->taskPrefix . $this->evaluationSyncTime;
        $result = json_decode($this->persistRedis->hget($key, $account_id), true);
        if ($time) {
            foreach ($time as $field => $value) {
                $result[$field] = $value;
            }
            $this->persistRedis->hset($key, $account_id, json_encode($result));
            return true;
        }
        return $result;
    }

    /**
     * 设置和获取纠纷最后抓取时间
     */
    public function taskIssueTime($account_id = 0, $time = 0)
    {
        $key = $this->taskPrefix . $this->issueSyncTime;
        if ($account_id && $time) {
            $this->persistRedis->hset($key, $account_id, $time);
            return true;
        }
        if($account_id){
            $result = $this->persistRedis->hget($key, $account_id);
        }else{
            $result = $this->persistRedis->hGetAll($key);
        }
        return $result;
    }

    /**
     * 根据账号别称获取账号数据
     * @param string $nick
     * @return array|mixed
     */
    public function getAccountByNick($nick)
    {
        $return = [];
        if(empty($nick)){
            return $return;
        }
        $key = $this->nickPrefix . $nick;
        $need_select = false;
        $where = [];
        if($this->isExists($key)){
            $return = $this->cacheObj()->hGetAll($key);
            $redis_update_time = isset($return['redis_update_time']) ? $return['redis_update_time'] : 0;
            //过期时间10分钟
            if($return['id']==0 && (time() - $redis_update_time) > (60 * 10)){
                $need_select = true;
                $where['user_nick'] = $nick;
            }
            $return = $return['id'] == 0 ? [] : $return;
        }else{
            $need_select = true;
        }
        //不需要查表，直接返回
        if(!$need_select){
            return $return;
        }
        //查询数据库
        $has_find = false;//是否找到
        if($dataList = $this->model->where($where)->field(true)->select()){
            foreach ($dataList as $value) {
                $value = $value->toArray();
                if(empty($value['user_nick'])){
                    continue;
                }
                $value['redis_update_time'] =  time();//记录redis更新时间
                $key_tmp = $this->nickPrefix . $value['user_nick'];
                foreach ($value as $k => $v) {
                    $this->setData($key_tmp, $k, $v);
                }
                //取出当前值
                if($value['user_nick'] == $nick){
                    $has_find = true;
                    $return = $value;
                }
            }
        }
        //如果没有找到，存入一个id为0的记录，防止频繁查表
        if(!$has_find){
            $this->cacheObj()->hSet($key, 'id', 0);
            $this->cacheObj()->hSet($key, 'redis_update_time', time());
        }
        return $return;
    }
    
}
