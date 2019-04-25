<?php


namespace app\common\cache\driver;
use app\common\cache\Cache;
use app\common\model\oberlo\OberloAccount as ModelOberloAccount ;
use app\common\traits\CacheTable;

class OberloAccount extends  Cache
{
    use CacheTable;
    private $orderSyncTime = 'order_sync_time';
    private $listingSyncTime = 'listing_sync_time';
    private $listingUpdateTime = 'listing_update_time';
    private $taskPrefix = 'task:oberlo:';

    public function __construct()
    {
        parent::__construct();
        $this->model(ModelOberloAccount::class);
    }

    public function getId($id=0)
    {
        return $this->getAllCount($id);
    }

    public function getAllCount($id = 0)
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
//        return $this->getTableRecord($id);
    }

    public function clearCache($id=0)
    {
        $this->delTableRecord($id);
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
     * 账号获取listing更新时间
     * @param int $accountId
     * @return int
     */
    public function getListingUpdateTime($accountId)
    {
        $key = $this->taskPrefix . $this->listingUpdateTime;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = $this->persistRedis->hget($key, $accountId);
            return $arr ?? 0;
        }
        return 0;
    }

    /**
     * 设置账号同步listing的更新时间
     * @param int $account_id
     * @param int $syncTime
     * @return array|mixed
     */
    public function setListingUpdateTime($account_id, $syncTime)
    {
        $key = $this->taskPrefix . $this->listingUpdateTime;
        if (!empty($syncTime)) {
            $this->persistRedis->hset($key, $account_id, $syncTime);
        }
    }

    private $setKey = 'oberlo:list:product:set';

    private $key = 'oberlo:list:product';

    public function setProductCache($accountId, $product_id, array $data)
    {
        $key = $this->getKeyName($accountId);
        $this->redis->sAdd($this->setKey, $accountId);
        return $this->redis->hSet($key, $product_id, json_encode($data));
    }

    public function getProductCache($accountId, $product_id)
    {
        $key = $this->getKeyName($accountId);
        $cache = $this->redis->hGet($key, $product_id);
        return $cache ? json_decode($cache, true) : [];
    }

    private function getKeyName($accountId)
    {
        return $this->key . ':' . $accountId;
    }

}
