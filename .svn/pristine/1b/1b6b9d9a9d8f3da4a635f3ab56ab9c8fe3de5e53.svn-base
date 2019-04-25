<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\Order;
use app\common\model\report\ReportStatisticByDate;
use app\common\service\OrderStatusConst;
use think\Db;
use think\Exception;

/** 统计日期
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/1
 * Time: 19:14
 */
class StatisticDate
{
    protected $reportStatisticByDateModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByDateModel)) {
            $this->reportStatisticByDateModel = new ReportStatisticByDate();
        }
    }

    /** 列表数据
     * @param $data
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function lists($data)
    {
        $where = [];
        $this->where($data, $where);
        $lists = $this->reportStatisticByDateModel->field(true)->where($where)->select();
        return $lists;
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where)
    {
        if (isset($data['year']) && !empty($data['year'])) {
            $where['year'] = ['eq', $data['year']];
        }
        if (isset($data['month']) && !empty($data['month'])) {
            $where['month'] = ['eq', $data['month']];
        }
        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $where['category_id'] = ['eq', $data['category_id']];
        }
        if (isset($data['goods_id']) && !empty($data['goods_id'])) {
            $where['goods_id'] = ['eq', $data['goods_id']];
        }
        if (isset($data['sku_id']) && !empty($data['sku_id'])) {
            $where['sku_id'] = ['eq', $data['sku_id']];
        }
    }

    /**
     * 数据重置
     * @param int $begin_time
     * @param int $end_time
     * @param int $sku_id
     */
    public function resetReport($begin_time = 0, $end_time = 0, $sku_id = 0)
    {
        $where['o.status'] = ['<>', OrderStatusConst::SaidInvalid];
        if (!empty($channel_id)) {
            $where['o.channel_id'] = ['eq', $channel_id];
        }
        $condition = timeCondition($begin_time, $end_time);
        if (!empty($condition)) {
            $where['o.create_time'] = $condition;
        }
        if (!empty($sku_id)) {
            $where['d.sku_id'] = ['eq', $sku_id];
        }
        $join = ['order_detail d', 'd.order_id = o.id'];
        $orderList = (new Order())->alias('o')->field('o.shipping_time,o.status,o.create_time,d.sku_id,d.goods_id')->join($join)->where($where)->where('o.status', '>', OrderStatusConst::ForDistribution)->select();
        unset($where);
        $orderData = [];
        $cache = Cache::store('goods');
        foreach ($orderList as $k => $value) {
            $year = date('Y', $value['create_time']);
            $month = date('m', $value['create_time']);
            //查找分类信息
            $goodsInfo = $cache->getGoodsInfo($value['goods_id']);
            $key = $year . '-' . $month . '-' . $value['sku_id'] . '-' . $value['goods_id'] . '-' . $goodsInfo['category_id'];
            if (isset($orderData[$key]['order_turnover'])) {
                $orderData[$key]['order_turnover']++;
            } else {
                $orderData[$key] = [];
                $orderData[$key]['order_turnover'] = 1;
            }
            if (!empty($value['shipping_time'])) {  //已发货，统计销售数
                if (isset($orderData[$key]['sale_quantity'])) {
                    $orderData[$key]['sale_quantity']++;
                } else {
                    $orderData[$key]['sale_quantity'] = 1;
                }
            }
            if ($value['status'] == OrderStatusConst::HaveRefund) {  //已退款
                if (isset($orderData[$key]['refund_quantity'])) {
                    $orderData[$key]['refund_quantity']++;
                } else {
                    $orderData[$key]['refund_quantity'] = 1;
                }
            }
        }
        Db::startTrans();
        try {
            $reportModel = new ReportStatisticByDate();
            foreach ($orderData as $key => $value) {
                $data = explode('-', $key);
                $where['year'] = ['eq', $data[0]];
                $where['month'] = ['eq', $data[1]];
                $where['sku_id'] = ['eq', $data[2]];
                if (($reportModel->where($where)->count()) == 0) {
                    $insert = $value;
                    $insert['year'] = $data[0];
                    $insert['month'] = $data[1];
                    $insert['sku_id'] = $data[2];
                    $insert['goods_id'] = $data[3];
                    $insert['category_id'] = $data[4];
                    (new ReportStatisticByDate())->allowField(true)->isUpdate(false)->save($insert);
                } else {
                    (new ReportStatisticByDate())->where($where)->setInc('order_turnover', $value['order_turnover']);
                    if (isset($value['sale_quantity'])) {
                        (new ReportStatisticByDate())->where($where)->setInc('sale_quantity', $value['sale_quantity']);
                    }
                    if (isset($value['refund_quantity'])) {
                        (new ReportStatisticByDate())->where($where)->setInc('refund_quantity', $value['refund_quantity']);
                    }
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }
    }
}