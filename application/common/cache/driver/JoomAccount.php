<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\joom\JoomAccount as JoomAccountModel;
use app\common\traits\CacheTable;

/**
 * Joom账号缓存
 * Created by NetBeans.
 * User: Rondaful
 * Date: 2018/1/2
 * Time: 19:43
 */
class JoomAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:joom:';
    private $listingSyncTime = 'listing_sync_time';

    use CacheTable;

    public function __construct()
    {
        $this->model(JoomAccountModel::class);
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


    /**
     * 获取帐号API请求自增次数
     * @param $shop_id
     * @return bool|string
     */
    public function getApiTimes($shop_id)
    {
        $accouont = Cache::store('JoomShop')->getAccountId($shop_id);
        $key = $this->taskPrefix . 'account_times:'.$accouont;
        $int = $this->redis->incr($key);
        if ($int == 1) {
            $this->redis->expire($key, 60);
        }
        if($int > 250){
            return false;
        }

        return $int;
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
