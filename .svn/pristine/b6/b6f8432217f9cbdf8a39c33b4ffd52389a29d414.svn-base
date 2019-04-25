<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\walmart\WalmartAccount as WalmartAccountModel;
use app\common\traits\CacheTable;

/**
 * walmart账号缓存
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/6/7
 * Time: 11:43
 */
class WalmartAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:walmart:';
    private $listingSyncTime = 'listing_sync_time';
    private $listintlistkey = 'listing_list';

    use CacheTable;

    public function __construct()
    {
        $this->model(WalmartAccountModel::class);
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

    /**
     * 获取该帐号店铺的listint列表；
     * @param $accountId
     * @return array|mixed|string
     */
    public function getListinglist($accountId)
    {
        $key = $this->taskPrefix . $this->listintlistkey;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = $this->persistRedis->hget($key, $accountId);
            $arr = empty($arr)? ['product' => [], 'info' => [], 'variant' => []] : json_decode($arr, true);
            return $arr;
        }
        return ['product' => [], 'info' => [], 'variant' => []];
    }

    /**
     * @title 保存该帐号店铺的listint列表,分3种形式来保存
     * @param $account_id 账号id
     * @param $data ['key' => , 'value' =>]
     * @param $type 'product', 'info', 'variant'
     * @return bool
     */
    public function addListingdata($account_id, $data, $type) {
        if(!in_array($type, ['product', 'info', 'variant'])) {
            return false;
        }
        if(!isset($data['key'], $data['value'])) {
            return false;
        }

        $key = $this->taskPrefix . $this->listintlistkey;
        if (!empty($syncTime)) {
            $old = $this->getListinglist($account_id);
            $old[$type][$data['key']] = $data['value'];
            $this->persistRedis->hset($key, $account_id, json_encode($old));
        }
        return true;
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
}
