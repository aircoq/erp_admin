<?php
namespace app\report\service;

use app\common\model\Order;
use app\common\model\report\ReportStatisticByCountry;
use app\common\service\OrderStatusConst;
use think\Db;
use think\Exception;

/** 国家统计
 * Created by PhpStorm.
 * User: phill1
 * Date: 2017/8/1
 * Time: 19:13
 */
class StatisticCountry
{
    protected $reportStatisticByCountryModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByCountryModel)) {
            $this->reportStatisticByCountryModel = new ReportStatisticByCountry();
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
        $lists = $this->reportStatisticByCountryModel->field(true)->where($where)->select();
        return $lists;
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where)
    {
        if (isset($data['goods_id']) && !empty($data['goods_id'])) {
            $where['goods_id'] = ['eq', $data['goods_id']];
        }
        if (isset($data['sku_id']) && !empty($data['sku_id'])) {
            $where['sku_id'] = ['eq', $data['sku_id']];
        }
        if (isset($data['country_code']) && !empty($data['country_code'])) {
            $where['country_code'] = ['eq', $data['country_code']];
        }
        $data['date_b'] = isset($data['date_b']) ? $data['date_b'] : 0;
        $data['date_e'] = isset($data['date_e']) ? $data['date_e'] : 0;
        $condition = timeCondition($data['date_b'], $data['date_e']);
        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($condition)) {
            $where['dateline'] = $condition;
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
        $condition = timeCondition($begin_time, $end_time);
        if (!empty($condition)) {
            $where['o.create_time'] = $condition;
        }
        if (!empty($sku_id)) {
            $where['d.sku_id'] = ['eq', $sku_id];
        }
        $join = ['order_detail d', 'd.order_id = o.id'];
        $orderList = (new Order())->alias('o')->field('o.country_code,o.shipping_time,o.status,o.create_time,d.sku_id,d.goods_id')->join($join)->where($where)->where('o.status', '>', OrderStatusConst::ForDistribution)->select();
        unset($where);
        $orderData = [];
        foreach ($orderList as $k => $value) {
            $time = strtotime(date('Y-m-d', $value['create_time']));
            $key = $value['country_code'] . '-' . $value['sku_id'] . '-' . $time . '-' . $value['goods_id'];
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
            $reportModel = new ReportStatisticByCountry();
            foreach ($orderData as $key => $value) {
                $data = explode('-', $key);
                $where['country_code'] = ['eq', $data[0]];
                $where['sku_id'] = ['eq', $data[1]];
                $where['dateline'] = ['eq', $data[2]];
                if (($reportModel->where($where)->count()) == 0) {
                    $insert = $value;
                    $insert['country_code'] = $data[0];
                    $insert['sku_id'] = $data[1];
                    $insert['dateline'] = $data[2];
                    $insert['goods_id'] = $data[3];
                    (new ReportStatisticByCountry())->allowField(true)->isUpdate(false)->save($insert);
                } else {
                    (new ReportStatisticByCountry())->where($where)->setInc('order_turnover', $value['order_turnover']);
                    if (isset($value['sale_quantity'])) {
                        (new ReportStatisticByCountry())->where($where)->setInc('sale_quantity', $value['sale_quantity']);
                    }
                    if (isset($value['refund_quantity'])) {
                        (new ReportStatisticByCountry())->where($where)->setInc('refund_quantity', $value['refund_quantity']);
                    }
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }
    }
}