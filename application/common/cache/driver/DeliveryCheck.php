<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/11/14
 * Time: 14:23
 */

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\warehouse\service\PickingPackageService;

class DeliveryCheck extends Cache
{


    /**
     * 单品复核 sku 对应 包裹 数量
     */
    const HASH_KEY = 'singleChecking:skuMapPackageQuantity';
    /**
     * 单品复核 多件时，临时存储已检 数 sku 对应 包裹 数量
     */
    const RESIDUAL_HASH_KEY = 'singleChecking:residual';
    /**
     * 单品等待处理的数量
     */
    const CAChE_SINGLE_WAIT_COUNT = "singleChecking:single_wait_count";
    /**
     * 用户与检货单绑定
     */
    const CACHE_USER_PICKING_ID = "singleChecking:user_picking_id";
    /**
     * 每当用户扫拣货单进入，记录他
     */
    const CACHE_SINGLE_PEOPLE = "singleChecking:users";
    /**
     * 作废sku
     */
    const CACHE_SINGLE_INVALID_SKU = 'singleChecking:invalid_sku';
    /**
     * 拣货单与播种车篮子绑定
     */
    const CACHE_PICKING_BASKET = "twiceSorting:pickingMapBasket";

    /**
     * 二次分拣主缓存
     */
    const CACHE_PICKING_PACKAGE = "twiceSorting:pickingMapPackage";
    /**
     * 二次分拣用户绑定拣货单
     */
    const CACHE_USER_TWICE = "twiceSorting:userBindPickingId";
    /**
     * 二次分拣拣货单绑定用户
     */
    const CACHE_PICKING_USER_TWICE = 'twiceSorting:PickingIdBindUserId';

    /**
     * 二次分拣 sku_id 下架数量
     */
    const CACHE_SKU_COUNT = 'twiceSorting:SkuCount';

    const CACHE_TWICE_LOCK = 'twiceSorting:Lock';

    /**
     * 单品确认锁
     */
    const CACHE_SINGLE_SURE_SKU_LOCK = 'singleChecking:sureSkuLock';

    /**
     * 按单复核确认锁
     */
    const CACHE_NUMBER_LOCK = "twiceSorting:PackingLock";
    /**
     * 并发锁
     */
    const Concurrent_LOCK = "cache:ConcurrentLock";


    /**
     * @title 用户与包裹单id绑定，万一停电下次进来自动匹配信息
     * @param $user_id
     * @param $packageId
     * @author starzhan <397041849@qq.com>
     */
    public function userPicking($user_id, $pickingId = null, $box_number = '')
    {
        $key = self::CACHE_USER_PICKING_ID . ":" . $user_id;
        if ($pickingId) {
            $data = [
                'picking_id' => $pickingId
            ];
            if ($box_number) {
                $data['box_number'] = $box_number;
            }
            return $this->redis->set($key, json_encode($data));
        } else {

            $tmp = $this->redis->get($key);
            return json_decode($tmp, true);
        }
    }

    public function removeUserPicking($user_id)
    {
        $key = self::CACHE_USER_PICKING_ID . ":" . $user_id;
        return $this->redis->del($key);
    }

    /**
     * @title 单品拣货单id 周转箱id 绑定用户
     * @param $pickingId
     * @param $boxId
     * @param int $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function singleCheckBindPickingUserId($pickingId, $boxId = 0, $user_id = 0)
    {
        $key = 'singleChecking:packing_user_id:' . $pickingId;
        if ($user_id) {
            return $this->redis->hSet($key, intval($boxId), $user_id);
        }
        return $this->redis->hGet($key, intval($boxId));
    }

    /**
     * @title 清除单品拣货单id 周转箱id 绑定用户
     * @param $pickingId
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function removeSingleCheckBindPickingUserId($pickingId)
    {
        $key = 'singleChecking:packing_user_id:' . $pickingId;
        return $this->redis->del($key);
    }

    /**
     * @title 设置单品复核sku与包裹的数量关联
     * @param int $pickingId
     * @param string $sku
     * @param  $package_id
     * @param int $quantity
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function setSkuMap(int $pickingId, int $skuId, $package_id, int $quantity)
    {
        $key = self::HASH_KEY . ':' . $pickingId . ":" . $skuId;
        return $this->redis->zAdd($key, $quantity, $package_id);
    }

    /**
     * @title 设置单品锁
     * @param $picking_id
     * @return bool
     * @author starzhan <397041849@qq.com>
     */
    public function singleCheckLock($package_id)
    {
        $key = 'singleChecking:lock:' . $package_id;
        $flag = $this->setnx($key);
        return $flag;
    }

    /**
     * @title 注释..
     * @param int $pickingId
     * @param string $sku
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getSkuMap(int $pickingId, int $skuId, $start = 0, $end = 0)
    {
        $key = self::HASH_KEY . ':' . $pickingId . ":" . $skuId;
        return $this->redis->ZRANGE($key, $start, $end, 1);
    }

    public function hasSkuCache(int $pickingId, int $skuId)
    {
        $key = self::HASH_KEY . ':' . $pickingId . ":" . $skuId;
        return $this->redis->exists($key);
    }

    public function getSkuMapByPackageId(int $pickingId, int $skuId, int $packageId)
    {
        $key = self::HASH_KEY . ':' . $pickingId . ":" . $skuId;
        return $this->redis->zScore($key, $packageId);
    }

    public function getAllSkuKeys(int $pickingId)
    {
        $key = self::HASH_KEY . ':' . $pickingId . ":*";
        return $this->redis->keys($key);
    }

    public function getAllWatchKeys(int $pickingId)
    {
        $key = 'singleChecking:watchCache:' . $pickingId . ':*';
        return $this->redis->keys($key);
    }

    public function destroy($number, $skuId)
    {
        $key = self::HASH_KEY . ':' . $number . ":" . $skuId;
        $this->redis->del($key);
    }

    public function incrby($number, $sku, $package_id, $value = -1)
    {
        $key = self::HASH_KEY . ':' . $number . ":" . $sku;
        return $this->redis->hIncrBy($key, $package_id, $value);
    }

    /**
     * @title 等待处理拣货单的包裹数
     * @param $pickingId
     * @param $count
     * @author starzhan <397041849@qq.com>
     */
    public function incrbySingleWaitCount($pickingId, $count = -1)
    {
        $key = self::CAChE_SINGLE_WAIT_COUNT . ':' . $pickingId;
        return $this->redis->incrBy($key, $count);
    }

    /**
     * @title 删除等待处理拣货单的包裹数
     * @param $pickingId
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function removeSingleWaitCount($pickingId)
    {
        $key = self::CAChE_SINGLE_WAIT_COUNT . ':' . $pickingId;
        return $this->redis->del($key);
    }

    /**
     * @title 获取等待处理的包裹数
     * @param $pickingId
     * @return bool|string
     * @author starzhan <397041849@qq.com>
     */
    public function getSingleWaitCount($pickingId)
    {
        $key = self::CAChE_SINGLE_WAIT_COUNT . ':' . $pickingId;
        return $this->redis->get($key);
    }

    public function setSingleWaitCount($pickingId, $value)
    {
        $key = self::CAChE_SINGLE_WAIT_COUNT . ':' . $pickingId;
        return $this->redis->set($key, $value);
    }

    public function remove($number, $skuId, $package_id)
    {
        $key = self::HASH_KEY . ':' . $number . ":" . $skuId;
        return $this->redis->zRem($key, $package_id);
    }

    public function getWatchCacheExpireTime()
    {
        return 86400 * 7;
    }

    /**
     * @title 单品多件临时缓存
     * @param int $pickingId
     * @param int $skuId
     * @param $package_id
     * @param int $quantity
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function watchCache($user_id, int $pickingId, int $skuId, $package_id, int $quantity = 0)
    {
        $key = 'singleChecking:watchCache:' . $pickingId . ":" . $skuId . ":" . $user_id;
        $expireTime = $this->getWatchCacheExpireTime();
        if ($quantity) {
            $this->singleKey($pickingId, $key);
            $flag = $this->redis->zAdd($key, $quantity, $package_id);
            $this->redis->expire($key, $expireTime);
            return $flag;
        }
        $flag = $this->redis->zScore($key, $package_id);
        return $flag;
    }


    public function watchCacheList($user_id, int $pickingId, int $skuId, $start = 0, $end = 0)
    {
        $key = 'singleChecking:watchCache:' . $pickingId . ":" . $skuId . ":" . $user_id;
        return $this->redis->ZRANGE($key, $start, $end, 1);
    }

    /**
     * @title 删除单品多件临时缓存
     * @param int $pickingId
     * @param int $skuId
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function removeWatchCache($user_id, int $pickingId, int $skuId, $package_id)
    {
        $key = 'singleChecking:watchCache:' . $pickingId . ":" . $skuId . ":" . $user_id;
        $this->redis->zRem($key, $package_id);
        $list = $this->redis->ZRANGE($key, 0, 0, 0);
        if (empty($list)) {
            $this->singleKey($pickingId, $key, 'del');
            return $this->redis->del($key);
        }

    }

    /**
     * @title 面单不存在临时存储
     * @param $pickingId
     * @param $packageId
     * @param int $value
     * @return bool|string
     * @author starzhan <397041849@qq.com>
     */
    public function noLabelCache($pickingId, $packageId, $value = 0)
    {
        $key = 'singleChecking:noLabelCache:' . $pickingId . ":" . $packageId;
        if ($value) {
            $flag = $this->redis->set($key, $value);
            $this->redis->expire($key, 600);
            return $flag;
        }
        return $this->redis->get($key);
    }

    /**
     * @title 设置和获取 拣货单和篮子之间的对应关系
     * @param int $pickingId
     * @param array $data
     * @return bool|mixed
     * @author starzhan <397041849@qq.com>
     */
    public function bindPickingBasket(int $pickingId, array $data = array())
    {
        $key = self::CACHE_PICKING_BASKET . ":" . $pickingId;
        if ($data) {
            return $this->redis->set($key, json_encode($data));
        }
        return json_decode($this->redis->get($key), true);
    }

    /**
     * @title 制造和获取 二次分拣 主缓存
     * @param $pickingId int 拣货单id
     * @param $key string key
     * @param $value mixed 在外面装填好
     * @author starzhan <397041849@qq.com>
     */
    public function bingTwiceMainCache($pickingId, $key, $value = null)
    {
        $hashKey = self::CACHE_PICKING_PACKAGE . ":" . $pickingId;
        if ($value) {
            return $this->redis->hSet($hashKey, $key, $value);
        }
        return $this->redis->hGet($hashKey, $key);
    }

    /**
     * @title 注销 二次分拣的缓存
     * @param $pickingId
     * @author starzhan <397041849@qq.com>
     */
    public function destroyTwiceCache($pickingId)
    {
        //1.删除主缓存
        $MainCacheKey = self::CACHE_PICKING_PACKAGE . ":" . $pickingId;
        $this->redis->del($MainCacheKey);
        //2.删除篮子缓存
        $basketCacheKey = self::CACHE_PICKING_BASKET . ":" . $pickingId;
        $this->redis->del($basketCacheKey);
    }

    /**
     * @title 用户绑定二次分拣拣货单id
     * @param $user_id
     * @param $picking_id
     * @author starzhan <397041849@qq.com>
     */
    public function userBindTwicePicking($user_id, $pickingId = null)
    {
        $key = self::CACHE_USER_TWICE . ":" . $user_id;
        if ($pickingId) {
            $key = $key . ":" . $pickingId;
            return $this->redis->set($key, 1);
        } else {
            $keys = $this->redis->keys($key . ":*");
            if ($keys) {
                $thisKey = reset($keys);
                $arr = explode(":", $thisKey);
                return (int)$arr[3];
            }
            return 0;
        }
    }

    /**
     * @title 解绑用户二次分拣拣货单id
     * @param $user_id
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function removeUserBindTwicePicking($user_id, $pickingId)
    {
        $key = self::CACHE_USER_TWICE . ":*:" . $pickingId;
        $keys = $this->redis->keys($key . "*");
        foreach ($keys as $k) {
            return $this->redis->del($k);
        }
    }

    /**
     * @title 自增 二次分拣 sku 下架数
     * @param $pickingId
     * @param $skuId
     * @param int $inc
     * @author starzhan <397041849@qq.com>
     */
    public function incTwiceSkuCount($pickingId, $skuId, $inc = -1)
    {
        $key = self::CACHE_SKU_COUNT . ":" . $pickingId;
        return $this->redis->hIncrBy($key, $skuId, $inc);
    }

    /**
     * @title 设置 二次分拣 sku 下架数
     * @param $pickingId
     * @param $skuId
     * @param $qty
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function setTwiceSkuCount($pickingId, $skuId, $qty)
    {
        $key = self::CACHE_SKU_COUNT . ":" . $pickingId;
        return $this->redis->hSet($key, $skuId, $qty);
    }

    /**
     * @title 查看当前 sku 下架数
     * @param $pickingId
     * @param $skuId
     * @return string
     * @author starzhan <397041849@qq.com>
     */
    public function getTwiceSkuCount($pickingId, $skuId)
    {
        $key = self::CACHE_SKU_COUNT . ":" . $pickingId;
        return $this->redis->hGet($key, $skuId);
    }

    /**
     * @title 查看那个拣货单的所有下架数
     * @param $pickingId
     * @author starzhan <397041849@qq.com>
     */
    public function SelectTwiceSkuCount($pickingId)
    {
        $key = self::CACHE_SKU_COUNT . ":" . $pickingId;
        return $this->redis->hGetAll($key);
    }

    /**
     * @title clear this twiceSkuCount key
     * @param $pickingId
     * @author starzhan <397041849@qq.com>
     */
    public function clearTwiceSkuCount($pickingId)
    {
        $key = self::CACHE_SKU_COUNT . ":" . $pickingId;
        return $this->redis->del($key);
    }

    /**
     * @title 去掉那个sku
     * @param $pickingId
     * @param $skuId
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function delTwiceSkuCount($pickingId, $skuId)
    {
        $key = self::CACHE_SKU_COUNT . ":" . $pickingId;
        return $this->redis->hDel($key, $skuId);
    }

    public function setTwiceLock($pickingId, $skuId)
    {

        $key = self::CACHE_TWICE_LOCK . ":" . $pickingId . ":" . $skuId;
        $flag = $this->setnx($key);
        return $flag;
    }

    public function delTwiceLock($pickingId, $skuId)
    {
        $key = self::CACHE_TWICE_LOCK . ":" . $pickingId . ":" . $skuId;
        $this->redis->del($key);
    }

    /**
     * @title 统计拣货单里面的人
     * @param $pickingId
     * @param int $user_id
     * @return array|int
     * @author starzhan <397041849@qq.com>
     */
    public function SinglePickingUser($pickingId, $user_id = 0)
    {
        $key = self::CACHE_SINGLE_PEOPLE . ":" . $pickingId;
        if ($user_id) {
            return $this->redis->sAdd($key, $user_id);
        }
        return $this->redis->sMembers($key);
    }

    /**
     * @title 注释..
     * @param $pickingId
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function removeSinglePickingUser($pickingId)
    {
        $key = self::CACHE_SINGLE_PEOPLE . ":" . $pickingId;
        return $this->redis->del($key);
    }

    /**
     * @title 单品多件确认锁
     * @param $pickingId
     * @param $skuId
     * @return bool
     * @author starzhan <397041849@qq.com>
     */
    public function setSingleSureSkuLock($pickingId, $skuId, $user_id)
    {

        $key = self::CACHE_SINGLE_SURE_SKU_LOCK . ":" . $pickingId . ":" . $user_id . ":" . $skuId;
        $flag = $this->setnx($key);
        return $flag;
    }

    public function delSingleSureSkuLock($pickingId, $skuId, $user_id)
    {
        $key = self::CACHE_SINGLE_SURE_SKU_LOCK . ":" . $pickingId . ":" . $user_id . ":" . $skuId;
        $this->redis->del($key);
    }

    /**
     * @title 作废sku的个数
     * @param $pickingId
     * @param $skuId
     * @param $qty
     * @author starzhan <397041849@qq.com>
     */
    public function InvalidSku($pickingId, $skuId, $qty = 0)
    {
        $key = self::CACHE_SINGLE_INVALID_SKU . ":" . $pickingId;
        if ($qty) {
            return $this->redis->hIncrBy($key, $skuId, $qty);
        }
        return $this->redis->hGet($key, $skuId);
    }

    /**
     * @title 删除作废sku缓存
     * @param $pickingId
     * @author starzhan <397041849@qq.com>
     */
    public function DelInvalidSku($pickingId)
    {
        $key = self::CACHE_SINGLE_INVALID_SKU . ":" . $pickingId;
        $this->redis->del($key);
    }

    public function NumberPackingLock($packageId)
    {
        $key = self::CACHE_NUMBER_LOCK . ":" . $packageId;
        $flag = $this->setnx($key);
        return $flag;
    }

    public function delNumberPackingLock($packageId)
    {
        $key = self::CACHE_NUMBER_LOCK . ":" . $packageId;
        $this->redis->del($key);
    }

    public function setConcurrentLock($type, $user_id)
    {
        $key = self::Concurrent_LOCK . ":" . $type . ":" . $user_id;
        $flag = $this->setnx($key);
        return $flag;
    }

    public function delConcurrentLock($type, $user_id)
    {
        $key = self::Concurrent_LOCK . ":" . $type . ":" . $user_id;
        $this->redis->del($key);
    }

    public function setnx($key)
    {
        return $this->redis->set($key, 1, ['nx', 'ex' => 30]);
    }

    /**
     * @title 设置拣货单进入的类型 ，1 为 周转箱，2为拣货单
     * @param $pickingId
     * @param int $type
     * @return bool
     * @author starzhan <397041849@qq.com>
     */
    public function setPickCheckType($pickingId, $type = 1)
    {
        $key = 'singleChecking:PickCheckType:' . $pickingId;
        return $this->redis->set($key, $type, ['nx']);
    }

    public function getPickCheckType($pickingId)
    {
        $key = 'singleChecking:PickCheckType:' . $pickingId;
        return $this->redis->get($key);
    }

    public function delPickCheckType($pickingId)
    {
        $key = 'singleChecking:PickCheckType:' . $pickingId;
        $this->redis->del($key);
    }

    public function singleKey($pickingId, $key, $act = 'add')
    {
        $baseKey = 'singleChecking:keys:' . $pickingId;
        if ($act == 'add') {
            return $this->redis->hSet($baseKey, $key, 1);
        } else if ($act == 'del') {
            return $this->redis->hDel($baseKey, $key);
        }
    }

    public function getSingleKey($pickingId)
    {
        $baseKey = 'singleChecking:keys:' . $pickingId;
        return $this->redis->hGetAll($baseKey);
    }

    public function delSingleKeys($pickingId)
    {
        $baseKey = 'singleChecking:keys:' . $pickingId;
        $keys = $this->getSingleKey($pickingId);
        foreach ($keys as $key => $v) {
            $this->redis->del($key);
        }
        $this->redis->del($baseKey);
    }

    public function getAllWatchData($pickingId)
    {
        $result = [];
        $keys = $this->getSingleKey($pickingId);
        foreach ($keys as $key => $v) {
            $data = $this->redis->ZRANGE($key, 0, 0, 1);
            $keyArr = explode(":", $key);
            $row = [];
            $row['package_id'] = key($data);
            $row['quantity'] = reset($data);
            $row['created_id'] = $keyArr[4];
            $row['sku_id'] = $keyArr[3];
            $row['key'] = $key;
            $result[] = $row;
        }
        return $result;
    }

    public function createLog($pickingId, $data)
    {
        $baseKey = 'singleChecking:createLog:' . $pickingId;
        $this->redis->set($baseKey, json_encode($data));
        $this->redis->expire($baseKey, 86400 * 5);
    }

    public function delCreateLog($pickingId)
    {
        $baseKey = 'singleChecking:createLog:' . $pickingId;
        $this->redis->del($baseKey);
    }

}