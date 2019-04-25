<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonAccount as AmazonAccountModel;
use app\common\traits\CacheTable;

class AmazonAccount extends Cache
{
    const cachePrefix = 'table';
    // private $table = self::cachePrefix . ':amazon_account:table';
    private $tablePrefix = self::cachePrefix . ':amazon_account:';
    private $lastUpdateTime = 'last_update_time';
    private $lastExecuteTime = 'last_execute_time';
    private $listingLastRequestTime = 'listing_last_request_time';
    private $listingLastRequestLog = 'listing_last_request_log';
    private $listingLastGetReportLog = 'listing_last_get_report_log';
    private $listingLastUpdateLog = 'listing_last_update_log';
    private $lastRsyncListingSinceTime = 'last_rsyn_listing_since_time';

    private $lastDownloadHealthTime = 'download_health_time';

    private $feedbackLastRequestTime = 'feedback_last_request_time';
    private $feedbackLastRequestLog = 'feedback_last_request_log';
    private $feedbackLastGetReportLog = 'feedback_last_get_report_log';
    private $feedbackLastUpdateLog = 'feedback_last_update_log';

    private $taskPrefix = 'task:amazon:';
    private $orderSyncTime = 'order_sync_time';
    private $orderSyncTimeNew = 'order_sync_time_new';

    use CacheTable;

    public function __construct() {
        $this->model(AmazonAccountModel::class);
        parent::__construct();
    }

    /** 设置Amazon账号获取listing最后请求时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setListingLastRequestTime($account_id, $time)
    {
        $key = $this->tablePrefix . $this->listingLastRequestTime . ':' . $account_id;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** amazon账号获取listing最后请求时间
     * @param $account_id
     * @return array|mixed
     */
    public function getListingLastRequestTime($account_id)
    {
        $key = $this->tablePrefix . $this->listingLastRequestTime . ':' . $account_id;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return 0;
    }

    /** 设置Amazon账号获取listing最后请求日志
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setListingLastRequestLog($account_id, $time, $log)
    {
        $key = $this->tablePrefix . $this->listingLastRequestLog . ':' . $account_id;
        if (!empty($time)) {
            $this->redis->hset($key, $time, $log);
        }
    }

    /** 设置Amazon账号获取listing最后抓取报告日志
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setListingLastGetReportLog($account_id, $time, $log)
    {
        $key = $this->tablePrefix . $this->listingLastGetReportLog . ':' . $account_id;
        if (!empty($time)) {
            $this->redis->hset($key, $time, $log);
        }
    }

    /** 设置Amazon账号获取listing最后抓取报告日志
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setListingLastUpdateLog($account_id, $time, $log)
    {
        $key = $this->tablePrefix . $this->listingLastUpdateLog . ':' . $account_id;
        if (!empty($time)) {
            $this->redis->hset($key, $time, $log);
        }
    }

    /** 设置Amazon账号获取feedback最后请求时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setFeedbackLastRequestTime($account_id, $time)
    {
        $key = $this->tablePrefix . $this->feedbackLastRequestTime . ':' . $account_id;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** amazon账号获取feedback最后请求时间
     * @param $account_id
     * @return array|mixed
     */
    public function getFeedbackLastRequestTime($account_id)
    {
        $key = $this->tablePrefix . $this->feedbackLastRequestTime . ':' . $account_id;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return 0;
    }

    /** 设置Amazon账号获取Feedback最后请求日志
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setFeedbackLastRequestLog($account_id, $time, $log)
    {
        $key = $this->tablePrefix . $this->feedbackLastRequestLog . ':' . $account_id;
        if (!empty($time)) {
            $this->redis->hset($key, $time, $log);
        }
    }

    /** 设置Amazon账号获取Feedback最后抓取报告日志
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setFeedbackLastGetReportLog($account_id, $time, $log)
    {
        $key = $this->tablePrefix . $this->feedbackLastGetReportLog . ':' . $account_id;
        if (!empty($time)) {
            $this->redis->hset($key, $time, $log);
        }
    }

    /** 设置Amazon账号获取Feedback最后抓取报告日志
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setFeedbackLastUpdateLog($account_id, $time, $log)
    {
        $key = $this->tablePrefix . $this->feedbackLastUpdateLog . ':' . $account_id;
        if (!empty($time)) {
            $this->redis->hset($key, $time, $log);
        }
    }

    /** 设置amazon账号表最后更新的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setAmazonLastUpdateTime($account_id, $time)
    {
        $key = $this->tablePrefix . $this->lastUpdateTime . ':' . $account_id;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** amazon账号表抓单获取最后更新的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getAmazonLastUpdateTime($account_id)
    {
        $key = $this->tablePrefix . $this->lastUpdateTime . ':' . $account_id;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置amazon账号表抓单获取最后执行的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setAmazonLastExecuteTime($account_id, $time)
    {
        $key = $this->tablePrefix . $this->lastExecuteTime . ':' . $account_id;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** amazon账号表抓单获取最后执行的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getAmazonLastExecuteTime($account_id)
    {
        $key = $this->tablePrefix . $this->lastExecuteTime . ':' . $account_id;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /**
     * 获取账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAccount($id = 0)
    {
        return $id ? $this->getTableRecord($id) : $this->readTable($id, false);
    }

    /**
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        $this->delTableRecord($id);
    }

    /**
     * 拿取amazon账号最后下载订单更新的时间
     * @param int $account_id
     * @return array
     */
    public function getOrderSyncTime($account_id)
    {
        $key = $this->taskPrefix . $this->orderSyncTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            $result = json_decode($this->persistRedis->hget($key, $account_id), true);
            return $result ?? [];
        }
        return [];
    }

    /**
     * 设置amazon账号最后下载订单更新的时间
     * @param int $account_id
     * @param array $time
     */
    public function setOrderSyncTime($account_id, $time = [])
    {
        $key = $this->taskPrefix . $this->orderSyncTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, json_encode($time));
        }
    }
    
    /**
     * @desc 获取/设置  订单抓取时间记录
     * @author wangwei
     * @date 2019-3-22 14:11:07
     * @param int $account_id 账号id
     * @param array $time 数据
     * @param bool $cover 是否覆盖更新
     */
    public function taskOrderTime($account_id, $data = [], $cover = false)
    {
        $key = $this->taskPrefix . $this->orderSyncTimeNew;
        $result = json_decode($this->persistRedis->hget($key, $account_id), true);
        if ($data) {
            if(!$cover){
                foreach ($data as $field => $value){
                    if(is_array($value)){
                        foreach ($value as $kk=>$vv){
                            $result[$field][$kk] = $vv;
                        }
                    }else{
                        $result[$field] = $value;
                    }
                }
            }else{
                $result = $data;
            }
            $this->persistRedis->hset($key, $account_id, json_encode($result));
            return true;
        }
        return $result;
    }

    /** 设置amazon账号获取health最后更新时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setAmazonLastDownloadHealthTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastDownloadHealthTime;
        if (!empty($time)) {
            $this->redis->hset($key, $account_id, $time);
        }
    }

    /** amazon账号获取health最后更新时间
     * @param $account_id
     * @return array|mixed
     */
    public function getAmazonLastDownloadHealthTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastDownloadHealthTime;
        if ($this->redis->hexists($key, $account_id)) {
            return $this->redis->hget($key, $account_id);
        }
        return 0;
    }
    
    /**
     * @desc 获取授权信息（第三方授权信息新增了字段，后期可能会修改授权方式，这里定时刷新）
     * @author wangwei
     * @date 2018-9-29 13:55:13
     * @param number $id
     * @return array|mixed|NULL[]
     */
    public function getTableRecord($id = 0)
    {
        $recordData = [];
        if(!empty($id)){
            $key = $this->tableRecordPrefix . $id;
            if($this->isExists($key) && $info = $this->cacheObj()->hGetAll($key)){
                $redis_add_time = isset($info['redis_add_time']) ? $info['redis_add_time'] : 0 ;//redis添加时间
                //没有developer_access_key_id字段，10分钟查一次数据库
                if(!isset($info['developer_access_key_id']) && (time() - $redis_add_time) > 60 * 10){
                    $info = $this->readTable($id);
                }
            }else{
                $info = $this->readTable($id);
            }
            $recordData = $info;
        }else{
            $recordData = $this->readTable(0, false);
        }
        return $recordData;
    }
    
    /**
     * @desc 读取表记录信息（覆盖CacheTable->readTable()方法。新增redis添加时间字段；如果给定id，查指定id的数据）
     * @author wangwei
     * @date 2018-9-29 14:07:33
     * @param number $id
     * @return array|unknown|unknown[]
     */
    public function readTable($id = 0, $cache = true)
    {
        $newList = [];
        $where = $id ? ['id' => $id] : [];
        $dataList = $this->model->where($where)->field(true)->order('id asc')->select();
        foreach ($dataList as $key => $value) {
            $value = $value->toArray();
            $value['redis_add_time'] = time();//新增redis添加时间字段
            if ($cache) {
                $key = $this->tableRecordPrefix . $value['id'];
                foreach ($value as $k => $v) {
                    if($k!='invalid_message'){
                        $this->setData($key, $k, $v);
                    }
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
     * @desc 获取api授权 
     * @author wangwei
     * @date 2019-3-14 16:28:48
     * @param array $accRow
     */
    public function genApiAccount($accRow){
        $config = [
//             'token_id'=>'',
//             'token'=>'',
//             'seller_id'=>'',
//             'site'=>'',
//             'mws_auth_token'=>'',
        ];
        //自授权
        $is_self = false;
        $token_id = paramNotEmpty($accRow, 'access_key_id','');
        $token = paramNotEmpty($accRow, 'secret_key','');
        if($token_id && $token){
            $is_self = true;
        }
        //三方授权
        $is_third_party = false; 
        $developer_token_id = paramNotEmpty($accRow, 'developer_access_key_id','');
        $developer_token = paramNotEmpty($accRow, 'developer_secret_key','');
        $auth_token = paramNotEmpty($accRow, 'auth_token','');
        if($developer_token_id && $developer_token && $auth_token){
            $is_third_party = true;
        }
        //自授权和三方授权都不完整
        if(!($is_self || $is_third_party)){
            return $config;
        }
        //其他参数
        $merchant_id = paramNotEmpty($accRow, 'merchant_id','');
        $site = paramNotEmpty($accRow, 'site','');
        if(!($merchant_id && $site)){
            return $config;
        }
        //授权赋值
        $config['token_id'] = $token_id;
        $config['token'] = $token;
        $config['seller_id'] = $merchant_id;
        $config['site'] = $site;
        $config['mws_auth_token'] = '';
        if($is_third_party){
            $config['token_id'] = $developer_token_id;
            $config['token'] = $developer_token;
            $config['mws_auth_token'] = $auth_token;
        }
        return $config;
    }
    
    /**
     * @desc 获取api授权信息
     * @author wangwei
     * @date 2019-3-8 18:01:43
     * @param int $id 账号id
     * @return array
     */
    public function getApiAccount($id){
        $config = [];
        if(empty($id)){
            return $config;
        }
        $accRow = $this->getTableRecord($id);
        return $this->genApiAccount($accRow);
    }
    
}