<?php

namespace app\common\cache\driver;

use think\Exception;
use app\common\cache\Cache;
use app\common\model\PurchaseOrder as PurchaseOrderModel;

/**
 * @desc 采购单缓存
 * @author Jimmy <554511322@qq.com>
 * @date 2018-01-22 20:23:11
 */
class PurchaseOrder extends Cache
{

    private $key = 'task:PurchaseOrder:logistics';   //数据缓存;

    /**
     * @desc 设置外部流水号
     * @param int $orderId 采购单ID
     * @param int $externalNumber 外部流水号
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-22 20:32:11
     */

    public function addExternalNumber($orderId, $externalNumber)
    {
        try {
            if ($orderId && $externalNumber) {
                $value = implode('-', [$orderId, $externalNumber]);
                $score = time();
                //插入
                $this->redis->zAdd($this->key, $score, $value);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 设置外部流水号
     * @param int $orderId 采购单ID
     * @param int $externalNumber 外部流水号
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-22 20:32:11
     */
    public function delExternalNumber($orderId, $externalNumber)
    {
        try {
            if ($orderId && $externalNumber) {
                $value = implode('-', [$orderId, $externalNumber]);
                //删除
                $this->redis->zRem($this->key, $value);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 获取外部流水号,根据分值(时间区域)
     * @param int $from 起始分值
     * @param int $to 终点分值
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-23 17:51:11
     */
    public function getExternalNumber($from = 0, $to = 0)
    {
        try {
            $scoreFrom = $from ? $from : time() - 7200000;
            $scoreTo = $to ? $to : time();
            $res = $this->redis->zRangeByScore($this->key, $scoreFrom, $scoreTo);
            return $res;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 获得支付状态名
     */
    public function getPaymentStatusText(int $paymentStatus)
    {
        $status = (new PurchaseOrderModel())->getPaymentStatus();
        $list = array_combine(array_column($status, 'label'),array_column($status, 'name'));
        return isset($list[$paymentStatus]) ? $list[$paymentStatus] : '';
    }

}
