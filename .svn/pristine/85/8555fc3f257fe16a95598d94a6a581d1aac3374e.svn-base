<?php


namespace app\common\cache\driver;


use app\common\cache\Cache;
use app\common\model\shopee\ShopeeAccount as ModelShopeeAccount;
use app\common\model\shopee\ShopeeSite as ModelShopeeSite;
use app\common\traits\CacheTable;

class ShopeeAccount extends Cache
{
    use CacheTable;
    private $orderSyncTime = 'order_sync_time';
    private $listingSyncTime = 'listing_sync_time';
    private $listingUpdateTime = 'listing_update_time';
    private $taskPrefix = 'task:shopee:';
    private $returnSyncTime = 'return_sync_time';
    private $tablePrefix = 'table:shopee_account:';

    public function __construct()
    {
        parent::__construct();
        $this->model(ModelShopeeAccount::class);
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
    }

   /**
    * @desc 获取授权信息
    * @author 翟彬
    * @date 2018-12-29 18:10:13
    * @param number $id
    * @return array|mixed|NULL[]
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
            $recordData = $this->readTable($id, false);
        }
        return $recordData;
    }

    /**
     * @desc 读取表记录信息
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
            if ($cache) {
                $key = $this->tablePrefix . $value['id'];
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

    public function clearCache($id)
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
     * 账号获取return最后更新时间
     * @param int $accountId
     * @param int $syncRule
     * @return int
     */
    public function getReturnSyncTime($accountId, $syncRule)
    {
        $key = $this->taskPrefix . $this->returnSyncTime .':'.$syncRule;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = $this->persistRedis->hget($key, $accountId);
            return $arr ?? 0;
        }
        return 0;
    }

    /**
     * 设置账号同步return的时间
     * @param int $account_id
     * @param int $syncRule
     * @param int $syncTime
     * @return array|mixed
     */
    public function setReturnSyncTime($account_id, $syncRule, $syncTime)
    {
        $key = $this->taskPrefix . $this->returnSyncTime .':'.$syncRule;
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

    private $setKey = 'shopee:list:product:set';

    private $key = 'shopee:list:product';

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

    public function getSite(){
        $key = 'hash:ShopeeAccount:site';
        $is_exists = $this->redis->exists($key);
        if($is_exists){
            $accounts = $this->redis->hGetAll($key);
            $result = [];
            if($accounts){
                foreach ($accounts as $key=>$account){
                    $result[$key]=json_decode($account,true);
                }
            }
            return $result;
        }else{
            $ret = ModelShopeeSite::all();
            $result = [];
            foreach ($ret as $v){
                $result[$v['code']] = $v->toArray();
                $this->redis->hSet($key, $v['id'], json_encode($v));
            }
            return $result;
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
}
