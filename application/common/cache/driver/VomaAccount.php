<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\voma\VomaAccount as VomaAccountModel;
use app\common\traits\CacheTable;

class VomaAccount extends Cache
{
    const cachePrefix = 'table';
    private $table = self::cachePrefix . ':voma_account:table';
    private $tablePrefix = self::cachePrefix . ':voma_account:';
    private $lastUpdateTime = 'last_update_time';
    private $lastExecuteTime = 'last_execute_time';
    private $listingLastRequestTime = 'listing_last_request_time';
    private $listingLastRequestLog = 'listing_last_request_log';
    private $listingLastGetReportLog = 'listing_last_get_report_log';
    private $listingLastUpdateLog = 'listing_last_update_log';
    private $lastRsyncListingSinceTime = 'last_rsyn_listing_since_time';

    private $feedbackLastRequestTime = 'feedback_last_request_time';
    private $feedbackLastRequestLog = 'feedback_last_request_log';
    private $feedbackLastGetReportLog = 'feedback_last_get_report_log';
    private $feedbackLastUpdateLog = 'feedback_last_update_log';

    private $taskPrefix = 'task:voma:';
    private $orderSyncTime = 'order_sync_time';

    use CacheTable;

    public function __construct() {
        $this->model(VomaAccountModel::class);
        parent::__construct();
    }

    /** 设置Voma账号获取listing最后请求时间
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

    /** voma账号获取listing最后请求时间
     * @param $account_id
     * @return array|mixed
     */
    public function getListingLastRequestTime($account_id)
    {
        $key = $this->tablePrefix . $this->listingLastRequestTime . ':' . $account_id;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置Voma账号获取listing最后请求日志
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

    /** 设置Voma账号获取listing最后抓取报告日志
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

    /** 设置Voma账号获取listing最后抓取报告日志
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

    /** 设置Voma账号获取feedback最后请求时间
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

    /** voma账号获取feedback最后请求时间
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

    /** 设置Voma账号获取Feedback最后请求日志
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

    /** 设置Voma账号获取Feedback最后抓取报告日志
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

    /** 设置Voma账号获取Feedback最后抓取报告日志
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

    /** 设置voma账号表最后更新的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setVomaLastUpdateTime($account_id, $time)
    {
        $key = $this->tablePrefix . $this->lastUpdateTime . ':' . $account_id;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** voma账号表抓单获取最后更新的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getVomaLastUpdateTime($account_id)
    {
        $key = $this->tablePrefix . $this->lastUpdateTime . ':' . $account_id;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置voma账号表抓单获取最后执行的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setVomaLastExecuteTime($account_id, $time)
    {
        $key = $this->tablePrefix . $this->lastExecuteTime . ':' . $account_id;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** voma账号表抓单获取最后执行的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getVomaLastExecuteTime($account_id)
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
        return $this->getTableRecord($id);
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
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        $this->delTableRecord($id);
    }

    /**
     * 拿取voma账号最后下载订单更新的时间
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
     * 设置voma账号最后下载订单更新的时间
     *  @param int $account_id
     *  @param array $time
     */
    public function setOrderSyncTime($account_id, $time = [])
    {
        $key = $this->taskPrefix . $this->orderSyncTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, json_encode($time));
        }
    }
}