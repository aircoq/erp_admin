<?php

namespace app\common\cache\driver;

use think\Exception;
use app\common\cache\Cache;
use app\common\model\Ali1688Account as Ali1688AccountModel;

/**
 * @desc ali1688账号缓存
 * @author Jimmy <554511322@qq.com>
 * @date 2018-01-22 20:23:11
 */
class Ali1688Account extends Cache
{

    private $key = 'hash:Ali1688Account'; //数据缓存;

    /**
     * @desc 添加缓存数据
     * @param int $orderId 采购单ID
     * @param int $externalNumber 外部流水号
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-23 15:32:11
     */

    public function addData($data)
    {
        try {
            if ($data) {
                //插入或更新
                $this->redis->hSet($this->key, $data['order_prefix'], json_encode($data));
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 删除缓存数据
     * @param int $orderId 采购单ID
     * @param int $externalNumber 外部流水号
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-23 15:32:11
     */
    public function delData($data)
    {
        try {
            if ($data) {
                //插入或更新
                $this->redis->hDel($this->key, $data['order_prefix'], json_encode($data));
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 获取外部流水号,根据分值(时间区域)
     * @param int $from Description
     */
    public function getExternalNumber($from = 0, $to = 0)
    {
        try {
            $scoreFrom = $from ? $from : time() - 7200;
            $scoreTo = $to ? $to : time();
            $res = $this->redis->zRangeByScore($this->key, $scoreFrom, $scoreTo);
            return $res;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 获取外部流水号,根据分值(时间区域)
     * @param int $orderPrefix 外部流水号后四位
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-23 17:55:11
     */
    public function getData($orderPrefix)
    {
        try {
            $res = $this->redis->hGet($this->key, $orderPrefix);
            if (!$res) {
                //如果缓存被删除过了
                $map['order_prefix'] = $orderPrefix;
                //获取表里面的数据
                $data = Ali1688AccountModel::get(['order_prefix' => $orderPrefix]);
                if ($data) {
                    //插入缓存
                    $this->addData($data);
                    //返回数据
                    return $data;
                }
            }
            return json_decode($res, true);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @desc 添加根据外部流水号获取的物流信息
     * @param int $id 外部流水号
     * @param array $log 执行请求返回的结果数据信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-04-02 17:28:11
     */
    public function addShippmentLog($id, $log)
    {
        try {
            $key = 'task:PurchaseOrder:log:' . date('YmdH');
            $this->redis->hSet($key, $id, json_encode($log));
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

}
