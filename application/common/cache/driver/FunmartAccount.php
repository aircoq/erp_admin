<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\traits\CacheTable;
use app\common\model\fummart\FummartAccount as FummartAccountModel;

/**
 * Class FunmartAccount
 * Created by linpeng
 * updateTime: time 2019/3/13 11:36
 * @package app\common\cache\driver
 */
class FunmartAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:funmart:';
    // private $table = self::cachePrefix . ':funmart_account:table';
    private $tablePrefix = self::cachePrefix . ':funmart_account:';
    use CacheTable;


    public function __construct() {
        /** 现funmart平台 */
        $this->model(FummartAccountModel::class);
        parent::__construct();
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
     * 获取账号信息通过id
     * @param int $id
     * @return array|mixed
     */
    public function getAccountById($id)
    {
        return $this->getTableRecord($id);
    }

    /**
     * 获取帐号信息传ID为，此ID的，不传ID，则为全部；
     * @param $id
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
        return true;
    }

    /**
     * 设置funmart账号最后下载订单更新的时间
     * @param $accountId
     * @param array $data
     */
    public function setOrderSyncTime($accountId, $data = [])
    {
        $key = $this->taskPrefix . 'order_sync_time';
        if (!empty($data)) {
            $this->persistRedis->hset($key, $accountId, json_encode($data));
        }
    }

    /**
     * 设置funmart账号最后下载订单更新的时间
     * @param $accountId
     * @return array|mixed
     */
    public function getOrderSyncTime($accountId)
    {
        $key = $this->taskPrefix . 'order_sync_time';
        if ($this->persistRedis->hexists($key, $accountId)) {
            $result = json_decode($this->persistRedis->hget($key, $accountId), true);
            return $result ?? [];
        }
        return [];
    }

    public function setOrderToLocalError($accountId, $data = [])
    {
        $key = $this->taskPrefix . 'order_tolocal_err';
        if (!empty($data)) {
            $this->persistRedis->hset($key, $accountId, json_encode($data));
        }
    }
}
