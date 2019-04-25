<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\lazada\LazadaAccount as LazadaAccountModel;
use app\common\traits\CacheTable;
use think\Model;
/** 缓存调整
 * Created by PhpStorm.
 * User: lan
 * Date: 2018/5/17
 * Time: 14:01
 */
class LazadaAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:lazada:';
//    private $table = self::cachePrefix . ':lazada_account:table';
    private $tablePrefix = self::cachePrefix . ':lazada_account:';
    private $lastUpdateTime = 'update_time';
    private $lastExecuteTime = 'last_execute_time';
    private $lastRsyncListingTime = 'listing_last_rsyn_time';
    private $lastRsyncListingSinceTime = 'listing_last_rsyn_since_time';

    private $orderSyncTime = 'order_sync_time';
    use CacheTable;

    public function __construct()
    {
        $this->model(LazadaAccountModel::class);
        parent::__construct();
    }
    /** 设置lazada账号获取listing最后更新时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setLazadaLastRsyncListingSinceTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingSinceTime;
        if (!empty($time)) {
            return $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** lazada账号获取listing最后更新时间
     * @param $account_id
     * @return array|mixed
     */
    public function getLazadaLastRsyncListingSinceTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingSinceTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }
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
     * 获取全部账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAllAccounts($id = 0)
    {
        $list = $this->getTableRecord($id);
        if (empty($id)) {
            foreach ($list as &$l) {
                $l['account_name'] = $l['name'];
            }
        } else {
            $list['account_name'] = $list['name'];
        }
        return $list;
//        return $this->getTableRecord();
    }
    /** 设置lazada账号同步listing的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setLazadaLastRsynListingTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** lazada账号同步listing的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getLazadaLastRsynListingTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置lazada账号表订单最后更新的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setLazadaOrderLastUpdateTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastUpdateTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }



    /** lazada账号表抓单获取最后更新的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getLazadaOrderLastUpdateTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastUpdateTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置lazada账号表抓单获取最后执行的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setLazadaOrderLastExecuteTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastExecuteTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** lazada账号表抓单获取最后执行的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getLazadaOrderLastExecuteTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastExecuteTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 获取lazada账号信息
     * @param $id
     * @return array|mixed
     */
    public function readAccount($id = 0)
    {
        $lazadaModel = new LazadaAccountModel();
        $new_list = [];
        $account_list = $lazadaModel->field(true)->order('id asc')->select();
        foreach ($account_list as $key => $value) {
            $value = $value->toArray();
            $key = $this->tablePrefix . $value['id'];
            foreach ($value as $k => $v) {
                $this->setData($key, $k, $v);
            }
            $this->setTable($value['id'], $value['id']);
            $new_list[intval($value['id'])] = $value;
        }
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        } else {
            return $new_list;
        }
    }

    /**
     * 判断key是否存在
     * @param $key
     * @return bool
     */
    private function isExists($key)
    {
        if ($this->redis->exists($key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断域是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    private function isFieldExists($key, $field)
    {
        if ($this->redis->hExists($key, $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置值
     * @param $key
     * @param $field
     * @param $value
     */
    public function setData($key, $field, $value)
    {
        if (!$this->isFieldExists($key, $field)) {
            $this->redis->hSet($key, $field, $value);
        }
    }

    /**
     * 获取账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAccount($id = 0)
    {
        $accountData = [];
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isExists($key)) {
                $accountInfo = $this->redis->hGetAll($key);
            } else {
                $accountInfo = $this->readAccount($id);
            }
            $accountData = $accountInfo;
        } else {
            if ($this->isExists($this->table)) {
                $accountId = $this->redis->hGetAll($this->table);
                foreach ($accountId as $key => $aid) {
                    $key = $this->tablePrefix . $aid;
                    $accountData[$aid] = $this->redis->hGetAll($key);
                }
            } else {
                $accountData = $this->readAccount($id);
            }
        }
        return $accountData;
    }

    /**
     * 记录表一共有多少条记录
     * @param $field
     * @param $value
     */
    public function setTable($field, $value)
    {
        if (!$this->isFieldExists($this->table, $field)) {
            $this->redis->hSet($this->table, $field, $value);
        }
    }

    /**
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            $this->redis->del($key);
            $this->redis->hDel($this->table, $id);
        } else {
            $accountId = $this->redis->hGetAll($this->table);
            foreach ($accountId as $key => $aid) {
                $key = $this->tablePrefix . $aid;
                $this->redis->del($key);
            }
            $this->redis->del($this->table);
        }
    }

    /**
     * 获取信息
     * @param int $id
     * @return array|mixed
     */
    public function getTableRecord($id = 0)
    {

        $recordData = [];
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            if ($this->isExists($key)) {
                $info = $this->cacheObj()->hGetAll($key);
            } else {
                $info = $this->readTable($id);
            }
            $recordData = $info;
        } else {
            if ($this->isExists($this->tableLists)) {
                $recordId = $this->cacheObj()->hGetAll($this->tableLists);
                foreach ($recordId as $key => $aid) {
                    $key = $this->tableRecordPrefix . $aid;
                    $recordData[$aid] = $this->cacheObj()->hGetAll($key);
                }
            } else {

                $recordData = $this->readTable($id);
            }
        }
        return $recordData;
    }

    /**
     * 设置lazada账号最后下载订单更新的时间
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