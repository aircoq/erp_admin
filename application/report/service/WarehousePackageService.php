<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\OrderPackage;
use app\common\model\Warehouse;
use app\purchase\task\GetUnpackParcelsCount;
use app\common\model\ReportShippedByDate;
use app\common\model\ReportShortageByDate;
use app\common\model\ReportUnpackedByDate;
use app\common\model\ReportUnshippedByDate;
use app\common\service\Channel;
use app\report\task\WriteBackWarehouseShippedTask;
use app\report\task\WriteBackWarehouseShortageTask;
use app\report\task\WriteBackWarehouseUnShippedTask;
use think\Exception;

/**
 * @desc 仓库首页统计
 */
class WarehousePackageService
{

    private $showDay = 7;

    /**
     * 仓库统计
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($params = [])
    {
        $cache = Cache::handler();
        $cacheKey = 'cache:warehousePackage:date';
        if($cache->exists($cacheKey)){
            $reData = json_decode($cache->get($cacheKey),true);
            return $reData;
        }
        opcache_reset();
        $reData = [];
        $sum = [
            'id' => 0,
            'name' => '合计',
            'unpacked_package' => 0,
            'stock_package' => 0,
            'delivery' => 0,
            'not_conforming' => 0,
            'orders_audit' => 0,
        ];
        $warehouses = $this->getWarehouses();

        if ($warehouses) {
            $warehouseIds = array_column($warehouses, 'id');
            $allStockPackage =  $this->getStockPackage($warehouseIds);
            $allDelivery = $this->getDelivery($warehouseIds);
            $allOrderAudit = $this->getOrderAudit($warehouseIds);
            $allNotConforming = $this->getNotConforming($warehouseIds);
            foreach ($warehouses as $v) {
                $one = $v->toArray();
                $one['unpacked_package'] = $this->unpackedDetail($v['id'])['sum']; //未操作包裹
                $one['stock_package'] = $allStockPackage[$v['id']] ?? 0; //缺货包裹
                $one['delivery'] = $allDelivery[$v['id']] ?? 0; //未配货包裹
                $one['not_conforming'] = $allNotConforming[$v['id']] ?? 0; //未符合生成拣货单
                $one['orders_audit'] = $allOrderAudit[$v['id']] ?? 0; //问题订单包裹
                $reData[] = $one;

                $sum['unpacked_package'] += $one['unpacked_package'];
                $sum['stock_package'] += $one['stock_package'];
                $sum['delivery'] += $one['delivery'];
                $sum['not_conforming'] += $one['not_conforming'];
                $sum['orders_audit'] += $one['orders_audit'];
            }
        }
        $all = $sum['unpacked_package'] + $sum['stock_package'] + $sum['delivery'] + $sum['not_conforming'] + $sum['orders_audit'];
        $reData[] = $sum;
        $rate = [
            'id' => -1,
            'name' => '占比',
            'unpacked_package' => '--',
            'stock_package' => '--',
            'delivery' => '--',
            'not_conforming' => '--',
            'orders_audit' => '--',
        ];
        if ($all > 0) {
            $rate['unpacked_package'] = sprintf("%.2f", $sum['unpacked_package'] / $all * 100) . '%';
            $rate['stock_package'] = sprintf("%.2f", $sum['stock_package'] / $all * 100) . '%';
            $rate['delivery'] = sprintf("%.2f", $sum['delivery'] / $all * 100) . '%';
            $rate['not_conforming'] = sprintf("%.2f", $sum['not_conforming'] / $all * 100) . '%';
            $rate['orders_audit'] = sprintf("%.2f", $sum['orders_audit'] / $all * 100) . '%';
        }
        $reData[] = $rate;
        $cache->set($cacheKey,json_encode($reData),300);
        return $reData;
    }


    /**
     * 统计分组包裹
     * @param string $where
     * @param array $whereAnd
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPackageCountGroupWarehouseId($where = '', $whereAnd = [])
    {
        $warehouses = $this->getWarehouses();
        $warehouseIds = array_column($warehouses, 'id');
        $whereAnd['warehouse_id'] = ['in', $warehouseIds];
        $field = 'warehouse_id,channel_id,count(id) as qty';
        $count = (new OrderPackage())->field($field)->where($where)->where($whereAnd)->group('warehouse_id,channel_id')->select();
        return $count;
    }

    /**
     * 缺货包裹
     * @param $warehouseIds
     * @return array
     */
    private function getStockPackage($warehouseIds)
    {
        $where = [
            'warehouse_id' => ['in', $warehouseIds],
            'shipping_time' => 0,
            'distribution_time' => 0
        ];
        $whereAnd = '(status >> 21) = 1 and status != 4294967295';
        return $this->getAllCount($where, $whereAnd);
    }

    /**
     * 未配货包裹
     * @param $warehouseIds
     * @return int|string
     */
    private function getDelivery($warehouseIds)
    {
        $where = [
            'warehouse_id' => ['in', $warehouseIds],
            'shipping_time' => 0,
            'distribution_time' => 0,
        ];
        $whereAnd = '((status >> 21= 1 and status != 4294967295) or ((status >> 17) = 1 and (status & 3843) | 0 = 0))';
        return $this->getAllCount($where, $whereAnd);
    }

    /**
     * 未发货订单包裹
     * @param $warehouseIds
     * @return int|string
     */
    private function getUnshippedPackage($warehouseIds)
    {
        $where = [
            'warehouse_id' => ['in', $warehouseIds],
            'shipping_time' => 0,
        ];
        return $this->getCount($where);
    }


    /**
     * 未符合生成拣货单
     * @param $warehouseIds
     * @return int|string
     */
    private function getNotConforming($warehouseIds)
    {
        $where = [
            'warehouse_id' => ['in', $warehouseIds],
            'is_push' => ['<>', 3],
            'picking_id' => 0,
            'shipping_time' => 0,
            'distribution_time' => ['>', 0],
        ];
        return $this->getAllCount($where);
    }

    /**
     * 问题订单包裹
     * @param $warehouseIds
     * @return array
     */
    private function getOrderAudit($warehouseIds)
    {
        $where = [
            'p.warehouse_id' => ['in', $warehouseIds],
            'p.shipping_time' => 0,
        ];
        $whereAnd = 'p.status < 196608';
        $join[] = ['order o', 'p.order_id = o.id', 'left'];
        $count = (new OrderPackage())->alias('p')->join($join)->where($where)->where($whereAnd)
            ->group('warehouse_id')->column('count(distinct(p.id))', 'warehouse_id');
        return $count;
    }

    /**
     * 拉取需要统计的仓库信息
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWarehouses()
    {
        $where['type'] = 1;
        $field = 'id,name';
        $warehouseIds = (new Warehouse())->where($where)->field($field)->select();
        return $warehouseIds;
    }


    /**
     * 拉取需要统计的仓库信息Id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWarehouseIds()
    {
        $warehouses = $this->getWarehouses();
        $warehouseIds = [];
        if ($warehouses) {
            $warehouseIds = array_column($warehouses, 'id');
        }
        return $warehouseIds;
    }


    /**
     * 未操作包裹详情
     * @param $warehouseId
     * @return array
     */
    public function unpackedDetail($warehouseId)
    {
        $reData = [
            'wait_for_make_picking' => $this->getWaitForMakePicking($warehouseId), //未生成拣货
            'wait_for_packing' => $this->getWaitForPacking($warehouseId), //未包装包裹
            'package_not_collection' => $this->getPackageNotCollection($warehouseId), //未集包包裹
            'sum' => 0, //合数
        ];
        return $this->getSun($reData);
    }

    /**
     * 统计和
     * @param $reData
     * @return mixed
     */
    private function getSun(&$reData)
    {
        foreach ($reData as $v) {
            $reData['sum'] += $v;
        }
        return $reData;
    }

    /**
     * 未生成拣货
     * @param $warehouseId
     * @return int|string
     */
    private function getWaitForMakePicking($warehouseId)
    {
        $where = [
            'warehouse_id' => $warehouseId,
            'is_push' => 3,
            'picking_id' => 0,
            'package_collection_id' => 0,
            'shipping_time' => 0,
            'distribution_time' => ['>',0],
        ];
        return $this->getCount($where);
    }

    /**
     * 未包装包裹
     * @param $warehouseId
     * @return int|string
     */
    private function getWaitForPacking($warehouseId)
    {
        $where = [
            'warehouse_id' => $warehouseId,
            'packing_time' => 0,
            'shipping_time' => 0,
            'picking_id' => ['>', 0],
            'distribution_time' => ['>',0],
        ];
        return $this->getCount($where);
    }

    /**
     * 未集包包裹
     * @param $warehouseId
     * @return int|string
     */
    private function getPackageNotCollection($warehouseId)
    {
        $where = [
            'warehouse_id' => $warehouseId,
            'packing_time' => ['>', 0],
            'picking_id' => ['>', 0],
            'package_collection_time' => 0,
            'package_collection_id' => 0,
            'distribution_time' => ['>',0],
            'shipping_time' => 0,
        ];
        return $this->getCount($where);
    }

    /**
     * 统计符合条件的包裹数
     * @param $where
     * @return int|string
     */
    private function getCount($where)
    {
        $count = (new OrderPackage())->where($where)->count();
        $count = $count ? $count : 0;
        return $count;
    }

    /**
     * 统计符合条件的包裹数 并按照仓库分组
     * @param $where
     * @param string $whereAnd
     * @return array
     */
    private function getAllCount($where, $whereAnd = '')
    {
        $where['order_id'] = ['>', 0];
        $count = (new OrderPackage())->where($where)->where($whereAnd)
            ->group('warehouse_id')->column('count(id)', 'warehouse_id');
        return $count;
    }

    /**
     * 返回符合的某天时间戳
     * @param int $day
     * @return false|float|int
     */
    public function getTime($day = 0)
    {
        if (!$day) {
            $day = $this->showDay;
        }
        return strtotime(date('Y-m-d')) - ($day - 1) * 86400;
    }

    private function interval($secs, $day)
    {
        $result = [];
        for ($i = 0; $i < $day; $i++) {
            $result[] = $secs;
            $secs -= TIME_SECS_DAY;
        }
        return array_reverse($result);
    }

    //一维数组倒叙
    private function dataFlashback($data, $key = '')
    {
        $reData = [];
        if (!$key) {
            $count = count($data) - 1;
            for ($i = $count; $i >= 0; $i--) {
                $reData[] = $data[$i];
            }
        }
        return $reData;
    }

    /**
     *  未发货记录
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logUnfilled($params = [])
    {
        $model = new ReportUnshippedByDate();
        return $this->getRedata($model);
    }

    /**
     *  已发货记录
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logShipped($params = [])
    {
        $model = new ReportShippedByDate();
        return $this->getRedata($model);
    }

    /**
     *  未拆包记录
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logNotOpen($params = [])
    {
        $model = new ReportUnpackedByDate();
        return $this->getRedata($model);
    }

    /**
     *  缺货记录
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logStock($params = [])
    {
        $model = new ReportShortageByDate();
        return $this->getRedata($model);
    }


    /**
     * 封装返回数据
     * @param $model
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getRedata($model)
    {

        $time = $this->getTime();
        $where = [
            'warehouse_id' => ['in', $this->getWarehouseIds()],
            'dateline' => ['>=', $time],
        ];
        $field = 'warehouse_id,dateline,sum(quantity) as qty';
        $list = $model->field($field)->where($where)->group('warehouse_id,dateline')->select();

        $warehouses = $this->getWarehouses();
        $beginDay = strtotime(date('Y-m-d'));
        $days = $this->interval($beginDay, $this->showDay);
        $reData = [];
        foreach ($days as $v) {
            $reData[$v] = [
                0 => 0,
            ];
            foreach ($warehouses as $one) {
                $reData[$v][$one['id']] = 0;
            }
        }
        $today = $this->getCacheData($model);
        foreach ($today as $k => $v) {
            $list[] = [
                'warehouse_id' => $k,
                'qty' => $v,
                'dateline' => $beginDay,
            ];
        }
        foreach ($list as $v) {
            foreach ($reData as $key => &$day) {
                if ($v['dateline'] == $key) {
                    $day[$v['warehouse_id']] = $v['qty'];
                    $day[0] += $v['qty'];
                    break;
                }
            }
        }
        return ['days' => $days, 'data' => $reData];
    }

    /**
     * 未发货记录详情
     * @param $warehouseId
     * @param $dateline
     * @return array
     * @throws \Exception
     */
    public function logUnfilledDetails($warehouseId, $dateline)
    {
        $model = new ReportUnshippedByDate();
        return $this->getRedataDetials($model, $warehouseId, $dateline);
    }

    /**
     * 已发货记录详情
     * @param $warehouseId
     * @param $dateline
     * @return array
     * @throws \Exception
     */
    public function logShippedDetails($warehouseId, $dateline)
    {
        $model = new ReportShippedByDate();
        return $this->getRedataDetials($model, $warehouseId, $dateline);
    }

    /**
     * 缺货记录详情
     * @param $warehouseId
     * @param $dateline
     * @return array
     * @throws \Exception
     */
    public function logStockDetails($warehouseId, $dateline)
    {
        $model = new ReportShortageByDate();
        return $this->getRedataDetials($model, $warehouseId, $dateline);
    }

    /**
     * 封装返回详情数据
     * @param $model
     * @param $warehouseId
     * @param $dateline
     * @return array
     * @throws \Exception
     */
    private function getRedataDetials($model, $warehouseId, $dateline)
    {
        $channelServer = new Channel();
        $channels = $channelServer->getChannels();
        $reData = [];
        foreach ($channels as $v) {
            $reData[$v['id']] = [
                'channel_id' => $v['id'],
                'title' => $v['title'],
                'qty' => 0,
            ];
        }
        $reData[0] = [
            'channel_id' => 0,
            'title' => '合计',
            'qty' => 0,
        ];
        if ($dateline == date('Y-m-d')) {
            $list = $this->getCacheData($model, $warehouseId);
        } else {
            $dateline = strtotime($dateline);
            $where = [
                'warehouse_id' => $warehouseId,
                'dateline' => $dateline,
            ];
            $field = 'channel_id,quantity';
            $list = $model->field($field)->where($where)->select();
        }
        foreach ($list as $v) {
            $reData[$v['channel_id']]['qty'] = $v['quantity'];
            $reData[0]['qty'] += $v['quantity'];
        }
        return $reData;
    }

    public function getCacheData($mode, $warehouseId = 0)
    {
        $mode = get_class($mode);
        $mode = basename(str_replace('\\', '/', $mode));
        $cache = Cache::handler();
        $beginDay = strtotime(date('Y-m-d'));
        $cacheKey = 'cache:WarehousePackage:' . $beginDay . ':' . $mode;
        $reData = [];
        if ($cache->exists($cacheKey)) {

            $reData = json_decode($cache->get($cacheKey), true);
        } else {
            $class = $this->getClass($mode);
            $task = new $class;
            $list = $task->getDatas();
            $warehouseIds = $this->getWarehouseIds();
            foreach ($warehouseIds as $warehouseId) {
                $reData[$warehouseId] = [];
                $reData[0][$warehouseId] = 0;

            }
            switch ($mode) {
                case 'ReportUnpackedByDate':
                    foreach ($list as $k => $v) {
                        if (in_array($k, $warehouseIds)) {
                            $reData[0][$k] += $v;
                        }
                    }
                    break;
                default:
                    foreach ($list as $v) {
                        $reData[$v['warehouse_id']][] = [
                            'channel_id' => $v['channel_id'],
                            'quantity' => $v['qty'],
                        ];
                        $reData[0][$v['warehouse_id']] += $v['qty'];
                    }
            }
            $cache->set($cacheKey, json_encode($reData), 2 * 3600);
        }
        return $reData[$warehouseId] ?? [];
    }

    public function getClass($mode)
    {
        $all = [
            'ReportUnpackedByDate' => 'app\purchase\task\GetUnpackParcelsCount',
            'ReportShippedByDate' => 'app\report\task\WriteBackWarehouseShippedTask',
            'ReportUnshippedByDate' => 'app\report\task\WriteBackWarehouseUnShippedTask',
            'ReportShortageByDate' => 'app\report\task\WriteBackWarehouseShortageTask',
        ];
        return $all[$mode];
    }

    /**
     * 手动跑任务
     */
    public function manualRunTask()
    {
        try {
            $re = (new GetUnpackParcelsCount())->execute();
            $re = (new WriteBackWarehouseShippedTask())->execute();
            $re = (new WriteBackWarehouseUnShippedTask())->execute();
            $re = (new WriteBackWarehouseShortageTask())->execute();
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
        return true;
    }


}