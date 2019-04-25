<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\paypal\PaypalAccount as PaypalAccountModel;
use app\common\model\PaypalTransaction;
use app\common\traits\CacheTable;
use think\Exception;

/**
 * Paypal账号缓存
 * Created by NetBeans.
 * User: Rondaful
 * Date: 2017/12/14
 * Time: 17:37
 */
class PaypalAccount extends Cache
{
    private $tokenKey = 'token:paypal:';
    private $disputeSyncTime = 'sync_dispute';
    private $taskPrefix = 'task:paypa:';
    private $paypalSyncTime = "sync_paypal";

    use CacheTable;

    public function __construct()
    {
        $this->model(PaypalAccountModel::class);
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
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        $this->delTableRecord($id);
        return true;
    }

    public function setAccessToken($account_id, $access_token, $expire = 0) {
        if (empty($account_token)) {
            throw new Exception('保存access_token时，参数出错：access_token为空');
        }
        if (empty($account_id)) {
            throw new Exception('保存access_token时，参数出错：paypay帐号ID为空');
        }
        if (empty($expire)) {
            throw new Exception('保存access_token时，参数出错：expire有效期为空');
        }
        $key = $this->tokenKey. $account_id;
        $this->redis->set($key, $access_token);
        $this->redis->expire($key, $expire);
        return true;
    }

    public function getAccessTokenCache($account_id)
    {
        if (empty($account_id)) {
            throw new Exception('拿取access_token缓存时，参数出错：paypay帐号ID为空');
        }
        $key = $this->tokenKey. $account_id;
        $token = $this->redis->get($key);
        if (empty($token)) {
            return false;
        }
        return $token;
    }

    /**
     * 拿取ebay账号最后下载订单更新的时间
     * @param int $account_id
     * @return array
     */
    public function getDisputeSyncTime($account_id)
    {
        $key = $this->taskPrefix . $this->disputeSyncTime;
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
    public function setDisputeSyncTime($account_id, $time = [])
    {
        $key = $this->taskPrefix . $this->disputeSyncTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, json_encode($time));
        }
    }

    /**
     * 设置paypal订单最后拉取时间
     * @param $account_id
     * @param int $time
     */
    public function setPaypalSyncTime($account_id,$time)
    {
        $key = $this->taskPrefix . $this->paypalSyncTime;
        if(!empty($time))
        {
            $this->cacheObj(true)->hSet($key,$account_id,$time);
        }
    }

    /**
     * 获取paypal最后一次拉取订单时间
     * @param $account_id
     * @return mixed|string
     */
    public function getPaypalSyncTime($account_id)
    {
        $key = $this->taskPrefix . $this->paypalSyncTime;
        $time =  $this->cacheObj(true)->hGet($key,$account_id);
        if(empty($time))   //非特殊情况不会去数据库取数据
        {
           return 0;
        }
        return $time;
    }

    public function getAccountidsByName($names)
    {
        $allAccount = $this->getTableRecord();
        $ids = [];
        if(!empty($allAccount))
        {
           foreach ($allAccount as $account)
           {
               if(in_array($account['account_name'],$names))
               {
                   $ids[] = $account['id'];
               }
           }
        }
        return $ids;
    }
}
