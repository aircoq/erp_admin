<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\wish\WishAccount as WishAccountModel;
use app\common\model\wish\WishAccountHealthPayment;

/** 缓存调整
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/9
 * Time: 14:01
 */
class WishAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:wish:';
    private $table = self::cachePrefix . ':wish_account:table';
    private $tablePrefix = self::cachePrefix . ':wish_account:';
    private $lastUpdateTime = 'order_last_update_time';
    private $lastExecuteTime = 'order_last_execute_time';
    private $lastRsyncListingTime = 'listing_last_rsyn_time';
    private $lastRsyncListingSinceTime = 'listing_last_rsyn_since_time';
    private $lastDownloadHealthTime = 'download_health_time';
    private $healthPeyment = 'health_payment_record:';
    private $healthGoal = 'health_Goal';

    /** 设置wish账号获取listing最后更新时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setWishLastRsyncListingSinceTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingSinceTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** wish账号获取listing最后更新时间
     * @param $account_id
     * @return array|mixed
     */
    public function getWishLastRsyncListingSinceTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingSinceTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置wish账号获取health最后更新时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setWishLastDownloadHealthTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastDownloadHealthTime;
        if (!empty($time)) {
            $this->redis->hset($key, $account_id, $time);
        }
    }

    /** wish账号获取health最后更新时间
     * @param $account_id
     * @return array|mixed
     */
    public function getWishLastDownloadHealthTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastDownloadHealthTime;
        if ($this->redis->hexists($key, $account_id)) {
            return $this->redis->hget($key, $account_id);
        }
        return 0;
    }

    /** 设置wish账号同步listing的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setWishLastRsynListingTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** wish账号同步listing的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getWishLastRsynListingTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastRsyncListingTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置wish账号付款记录ID
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setWishAccountHealthPaymentRecord($account_id, $token, $id)
    {
        $key = $this->taskPrefix . $this->healthPeyment. $account_id;
        $hashkey = implode('-', $token);
        if (!empty($token)) {
            $this->redis->hset($key, $hashkey, $id);
        }
    }

    /** 获取wish账号付款记录ID
     * @param $account_id
     * @return array|mixed
     */
    public function getWishAccountHealthPaymentRecord($account_id, $token)
    {
        $key = $this->taskPrefix . $this->healthPeyment. $account_id;
        $hashkey = implode('-', $token);
        if ($this->redis->hexists($key, $hashkey)) {
            return $this->redis->hget($key, $hashkey);
        } else {
            $where['wish_account_id'] = $account_id;
            $where['trading_time'] = $token[0];  //交易时间
            $where['payment_id'] = $token[1];   //交易唯一一ID
            $where['money'] = $token[2];    //交易金额
            $id = WishAccountHealthPayment::where($where)->value('id');
            $id = empty($id)? 0 : $id;
            return $id;
        }
    }

    /** 设置wish账号表订单最后更新的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setWishOrderLastUpdateTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastUpdateTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** wish账号表抓单获取最后更新的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getWishOrderLastUpdateTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastUpdateTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return [];
    }

    /** 设置wish账号表抓单获取最后执行的时间
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setWishOrderLastExecuteTime($account_id, $time)
    {
        $key = $this->taskPrefix . $this->lastExecuteTime;
        if (!empty($time)) {
            $this->persistRedis->hset($key, $account_id, $time);
        }
    }

    /** wish账号表抓单获取最后执行的时间
     * @param $account_id
     * @return array|mixed
     */
    public function getWishOrderLastExecuteTime($account_id)
    {
        $key = $this->taskPrefix . $this->lastExecuteTime;
        if ($this->persistRedis->hexists($key, $account_id)) {
            return $this->persistRedis->hget($key, $account_id);
        }
        return 0;
    }

    /** 设置wish账号健康监控目标
     * @param $account_id
     * @param $time
     * @return array|mixed
     */
    public function setHealthGoal($goal)
    {
        $key = $this->taskPrefix . $this->healthGoal;
        if (!empty($time)) {
            $this->persistRedis->set($key, json_encode($goal));
        }
    }

    /** wish账号健康监控目标
     * @param $account_id
     * @return array|mixed
     */
    public function getHealthGoal()
    {
        $key = $this->taskPrefix . $this->healthGoal;
        if ($this->persistRedis->exists($key)) {
            return json_decode($this->persistRedis->get($key), true);
        }
        return [];
    }

    /** 获取wish账号信息
     * @param $id
     * @return array|mixed
     */
    private function readAccount($id = 0)
    {
        $wishModel = new WishAccountModel();
        $new_list = [];
        $account_list = $wishModel->field(true)->order('id asc')->select();
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
     * 更新缓存信息
     * @param $id
     * @param $field
     * @param $value
     */
    public function updateTableRecord($id, $field, $value)
    {
        if (!empty($id)) {
            $key = $this->tablePrefix . $id;
            if ($this->isFieldExists($key, $field)) {
                $this->redis->hSet($key, $field, $value);
            }
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
}