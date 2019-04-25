<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ebay\EbayAccount as EbayAccountModel;
use app\common\traits\CacheTable;
use think\Db;

/**
 * ebay账号缓存
 * Created by NetBeans.
 * User: Rondaful
 * Date: 2017/12/14
 * Time: 17:37
 */
class EbayAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:ebay:';
//    private $table = self::cachePrefix . ':ebay_account';
    private $tablePrefix = self::cachePrefix . ':ebay_account:';
    private $lastUpdateTime = 'order_last_update_time';
    private $lastExecuteTime = 'order_last_execute_time';
    private $listingSyncTime = 'listing_sync_time';
    private $listingEventsSyncTime = 'listing_events_sync_time';

    private $orderSyncTime = 'order_sync_time';

    use CacheTable;

    public function __construct()
    {
        $this->model(EbayAccountModel::class);
        parent::__construct();
    }

    /**
     *
     * @param array $time
     *
     * 下载纠纷未收到货 - inquiries   下载纠纷退换货-return
     * @return mixed
     */

    /**
     * 设置或者拿取ebay账号最后下载订单更新的时间
     * @param $account_id
     * @param $field 下载订单-order 下载站内信-gmsg（getmessage） 下载站内信-mmsg(membermsg) 下载纠纷取消订单-cancel 下载纠纷Case升级-case
     * @param array $time 时间总数组；
     * @return bool
     */
    public function ebayLastUpdateTime($account_id, $field, $time = [])
    {
        $key = 'hash:ebayAccountUpdateTime:'. $account_id;
        //时间数组为空时，为获取，不为空，为设置
        if(empty($time)) {
            $result = $this->redis->hget($key, $field);
            return empty($result)? [] : json_decode($result, true);
        } else {
            $this->redis->hset($key, $field, json_encode($time));
            return true;
        }
    }

    /**
     * ebay账号获取listing最后更新时间
     * @param int $account_id
     * @return array
     */
    public function getListingSyncTime($accountId)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = json_decode($this->persistRedis->hget($key, $accountId), true);
            return $arr ?? [];
        }
        return [];
    }

    /**
     * 设置ebay账号同步listing的时间
     * @param int $account_id
     * @param array $data
     * @return array|mixed
     */
    public function setListingSyncTime($account_id, array $data)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if (!empty($data)) {
            $this->persistRedis->hset($key, $account_id, json_encode($data));
        }
    }

    /**
     * ebay账号获取listing Events最后更新时间
     * @param int $account_id
     * @return array
     */
    public function getListingEventsSyncTime($account_id)
    {
        $key = $this->taskPrefix . $this->listingEventsSyncTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            $arr = $this->persistRedis->hget($key, $account_id);
            return $arr ?? 0;
        }
        return 0;
    }

    /**
     * 设置ebay账号同步listing Events的时间
     * @param int $account_id
     * @param array $time
     * @return array|mixed
     */
    public function setListingEventsSyncTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->listingEventsSyncTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    public function getAccount($id = 0) {
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
     * 获取账号信息通过id
     * @param int $id
     * @return array|mixed
     */
    public function getAccountById($id)
    {
        return $this->getTableRecord($id);
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
     * 拿取ebay账号最后下载订单更新的时间
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
     * 设置ebay账号最后下载订单更新的时间
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

    /**
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function channelAccount()
    {
        $key = "cache:channel_account";
        $res = $this->redis->get($key);

        if($res)
        {
            return json_decode($res,true);
        }

        $res = Db::table("ebay_account")->alias("e")->join("channel_user_account_map m","m.account_id=e.id and m.channel_id=1","LEFT")
               ->join("user u","u.id=m.seller_id","LEFT")
               ->field("e.id,u.realname,e.code")
               ->select();
        if($res)
        {
            $account = [];
            foreach ($res as $r)
            {
                $account[$r['id']] = $r;
            }
            $this->redis->setex($key,5*60,json_encode($account));
            return $account;
        }
        return [];
    }
}

