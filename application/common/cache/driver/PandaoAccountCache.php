<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-21
 * Time: 上午11:40
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;
use app\common\model\pandao\PandaoAccount;
use app\common\traits\CacheTable;

/**
 * pandao账号缓存
 * Class PandaoAccount
 * @package app\common\cache\driver
 */
class PandaoAccountCache extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:pandao:';
    private $orderSyncTime = 'order_sync_time';
    private $listingSyncTime = 'listing_sync_time';
    use CacheTable;

    public function __construct()
    {
        $this->model(PandaoAccount::class);
        parent::__construct();
    }

    /**
     * 账号获取listing最后更新时间
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
     * 设置账号同步listing的时间
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
     * 设置和抓取账号订单最后抓取时间
     * 等待到货-waitOrder 全部订单-aliexpressOrder
     */
    public function taskOrderTime($account_id, $time = [])
    {
        $key = $this->taskPrefix . $this->orderSyncTime;
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
            $recordData = $this->readTable($id, false);
        }
        return $recordData;
    }

}