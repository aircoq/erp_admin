<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\PickingDetail;

/**
 * 拣货单
 * Created by PhpStorm.
 * User: phill
 * Date: 2018/1/9
 * Time: 17:19
 */
class Picking extends Cache
{
    const prefix = 'picking';
    const detailPrefix = self::prefix . ':detail:';
    private $_make_picking_busy = 'cache:picking:make:busy';  //记录忙碌
    private $_off_shelf_busy = 'cache:picking:off:busy';  //记录下架忙碌
    private $_pc_off_shelf_busy = 'cache:picking:pc_off:busy';  //记录PC下架忙碌
    private $_bind_turnover_busy = 'cache:picking:bind:busy';  //记录绑定周转箱的忙碌
    private $_off_shelf_quantity = 'hash:picking:';            //拣货单下架数
    private $_turnover_quantity = 'hash:turnover:';            //周转箱

    private function key($picking_id, $sku_id)
    {
        return self::detailPrefix . $picking_id . ':' . $sku_id;
    }

    /**
     * 设置拣货单sku详情信息
     * @param $picking_id
     * @param $sku_id
     * @param $detail
     */
    public function setPickingDetail($picking_id, $sku_id, $detail)
    {
        $key = $this->key($picking_id, $sku_id);
        foreach ($detail as $k => $v) {
            if (!$this->persistRedis->hexists($key, $k)) {
                $this->persistRedis->hSet($key, $k, $v);
            } else {
                if ($k == 'quantity') {
                    $this->persistRedis->hIncrBy($key, $k, $v);
                }
            }
        }
        $this->setPickingDetailList($picking_id, $sku_id);
    }

    /**
     * 获取拣货单sku详情信息
     * @param $picking_id
     * @param $sku_id
     * @return array
     */
    public function getPickingDetailBySkuId($picking_id, $sku_id)
    {
        $detail = [];
        $key = $this->key($picking_id, $sku_id);
        if ($this->persistRedis->exists($key)) {
            $detail = $this->persistRedis->hGetAll($key);
        }
        return $detail;
    }

    /**
     * 设置拣货单sku数量
     * @param $picking_id
     * @param $sku_id
     * @param $quantity
     */
    public function setSkuQuantity($picking_id, $sku_id, $quantity)
    {
        $key = $this->key($picking_id, $sku_id);
        if (!$this->persistRedis->hexists($key, 'quantity')) {
            $this->persistRedis->hSet($key, 'quantity', $quantity);
        } else {
            $this->persistRedis->hIncrBy($key, 'quantity', $quantity);
        }
    }

    /**
     * 设置拣货单 sku 的目录
     * @param $picking_id
     * @param $sku_id
     */
    private function setPickingDetailList($picking_id, $sku_id)
    {
        $key = self::prefix . ':' . $picking_id;
        if (!$this->persistRedis->hexists($key, $sku_id)) {
            $this->persistRedis->hSet($key, $sku_id, $sku_id);
        }
    }

    /**
     * 获取拣货单sku所有详情
     * @param $picking_id
     * @return array
     */
    public function getPickingDetail($picking_id)
    {
        $detailData = [];
        $key = self::prefix . ':' . $picking_id;
        if ($this->persistRedis->exists($key)) {
            $list = $this->persistRedis->hGetAll($key);
            foreach ($list as $key => $sku_id) {
                $skuKey = $this->key($picking_id, $sku_id);
                $detailData[$sku_id] = $this->persistRedis->hGetAll($skuKey);
            }
        }
        return $detailData;
    }

    /**
     * 记录操作中
     */
    public function setBusy()
    {
        $this->redis->set($this->_make_picking_busy, 1, 60);
    }

    /**
     * 释放记录操作中
     */
    public function delBusy()
    {
        $this->redis->del($this->_make_picking_busy);
    }

    /**
     * 查看是否在忙碌状态
     * @return bool
     */
    public function isBusy()
    {
        if ($this->redis->exists($this->_make_picking_busy)) {
            return true;
        }
        return false;
    }

    /**
     * 记录下架操作中
     * @param $picking_id
     * @param $sku_id
     */
    public function setOffTheShelfBusy($picking_id, $sku_id)
    {
        $key = $this->_off_shelf_busy . $picking_id . $sku_id;
        $this->redis->set($key, 1, 5);
    }

    /**
     * 释放下架记录操作中
     * @param $picking_id
     * @param $sku_id
     */
    public function delOffTheShelfBusy($picking_id, $sku_id)
    {
        $key = $this->_off_shelf_busy . $picking_id . $sku_id;
        $this->redis->del($key);
    }

    /**
     * 查看下架是否在忙碌状态
     * @param $picking_id
     * @param $sku_id
     * @return bool
     */
    public function isOffTheShelfBusy($picking_id, $sku_id)
    {
        $key = $this->_off_shelf_busy . $picking_id . $sku_id;
        if ($this->redis->exists($key)) {
            return true;
        }
        return false;
    }

    /**
     * 记录绑定周转箱操作中
     * @param $picking_id
     */
    public function setBindBusy($picking_id)
    {
        $key = $this->_bind_turnover_busy . $picking_id;
        $this->redis->set($key, 1, 3);
    }

    /**
     * 删除绑定周转箱记录
     * @param $picking_id
     */
    public function delBindBusy($picking_id)
    {
        $key = $this->_bind_turnover_busy . $picking_id;
        $this->redis->del($key);
    }

    /**
     * 查看绑定周转箱是否在忙碌状态
     * @param $picking_id
     * @return bool
     */
    public function isBindBusy($picking_id)
    {
        $key = $this->_bind_turnover_busy . $picking_id;
        if ($this->redis->exists($key)) {
            return true;
        }
        return false;
    }

    /**
     * 获取拣货单sku 下架数量
     * @param $picking_id
     * @param int $sku_id
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPickingSkuOffQuantity($picking_id, $sku_id = 0)
    {
        $key = $this->_off_shelf_quantity . $picking_id;
        if (!$this->redis->exists($key)) {
            $pickingDetail = (new PickingDetail())->master()->field('sku_id,picking_quantity')->where(['picking_id' => $picking_id])->select();
            foreach ($pickingDetail as $k => $value) {
                $this->redis->hSet($key, $value['sku_id'], $value['picking_quantity']);
            }
            $this->redis->expire($key, 2);
        }
        if (!empty($sku_id)) {
            if ($this->redis->hExists($key, $sku_id)) {
                $data = $this->redis->hGet($key, $sku_id);
                return $data;
            }
        } else {
            $data = $this->redis->hGetAll($key);
            return $data;
        }
    }

    /**
     *  删除拣货单sku 下架数量缓存
     * @param $picking_id
     * @return array|string
     */
    public function delPickingSkuOffQuantity($picking_id)
    {
        $key = $this->_off_shelf_quantity . $picking_id;
        $this->redis->del($key);
    }

    /**
     * 设置周转箱内容
     * @param $turnover
     * @param $warehouse_id
     * @param $sku_id
     * @param $quantity
     */
    public function setTurnoverSkuInfo($turnover, $warehouse_id, $sku_id, $quantity)
    {
        $key = $this->_turnover_quantity . $warehouse_id . ':' . $turnover;
        $this->redis->hSet($key, $sku_id, $quantity);
    }

    /**
     * 获取周转箱sku信息
     * @param $turnover
     * @param $warehouse_id
     * @param int $sku_id
     * @return array|string
     */
    public function getTurnoverSkuInfo($turnover, $warehouse_id, $sku_id = 0)
    {
        $key = $this->_turnover_quantity . $warehouse_id . ':' . $turnover;
        if (!empty($sku_id)) {
            $data = $this->redis->hGet($key, $sku_id);
        } else {
            $data = $this->redis->hGetAll($key);
        }
        return $data;
    }

    /**
     * 周转箱缓存释放存在
     * @param $turnover
     * @param $warehouse_id
     * @return bool
     */
    public function isExistsTurnover($turnover, $warehouse_id)
    {
        $key = $this->_turnover_quantity . $warehouse_id . ':' . $turnover;
        if ($this->redis->exists($key)) {
            return true;
        }
        return false;
    }

    /**
     * 删除周转箱sku缓存
     * @param $turnover
     * @param $warehouse_id
     * @return bool
     */
    public function delTurnoverSkuInfo($turnover, $warehouse_id)
    {
        $key = $this->_turnover_quantity . $warehouse_id . ':' . $turnover;
        $this->redis->del($key);
    }

    /**
     * 记录PC下架操作中
     * @param $picking_id
     */
    public function setPcOffTheShelfBusy($picking_id)
    {
        $key = $this->_pc_off_shelf_busy . $picking_id;
        $this->redis->set($key, 1);
    }

    /**
     * 生成pc下架记录操作中
     * @param $picking_id
     */
    public function delPcOffTheShelfBusy($picking_id)
    {
        $key = $this->_pc_off_shelf_busy . $picking_id;
        $this->redis->del($key);
    }

    /**
     * 查看pc下架是否在忙碌状态
     * @param $picking_id
     * @return bool
     */
    public function isPcOffTheShelfBusy($picking_id)
    {
        $key = $this->_pc_off_shelf_busy . $picking_id;
        if ($this->redis->exists($key)) {
            return true;
        }
        return false;
    }
}