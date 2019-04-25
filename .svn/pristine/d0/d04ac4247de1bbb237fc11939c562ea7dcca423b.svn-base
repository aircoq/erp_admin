<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Created by NetBeans.
 * User: ERP
 * Date: 2017/6/26
 * Time: 20:03
 */

class GuanyiWarehouse extends Cache
{
    private $_zset_guanyi_package = 'zset:guanyi:package';
    
    /**
     * 管易下单任务列
     * @param int $order_package_id
     * @param int $deadline
     * @return boolean
     */
    public function addOrderPackage($order_package_id, $deadline)
    {
        $this->persistRedis->zAdd($this->_zset_guanyi_package, $deadline, $order_package_id);
        return true;
    }
    
    /**
     * 获取推送包裹ids
     * @param int $start
     * @param int $end
     */
    public function getOrderPackageIds($start, $end)
    {
        $ids =$this->persistRedis->zRange($this->_zset_guanyi_package, $start, $end);
        return $ids;
    }
    
    /**
     * 去除包裹集合
     * @param string $order_package_id
     * @return boolean
     */
    public function delOrderPackage($order_package_id)
    {
        $this->persistRedis->zDelete($this->_zset_guanyi_package, $order_package_id);
        return true;
    }
    
    /**
     * 包裹id是否在集合中
     * @param string $order_package_id
     * @return boolean
     */
    public function isGuanyiPackage($order_package_id)
    {
        if ($this->persistRedis->zScore($this->_zset_guanyi_package, $order_package_id) !== false) {
            return true;
        }
        
        return false;
    }
}