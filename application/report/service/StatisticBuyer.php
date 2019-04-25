<?php
namespace app\report\service;

use app\common\model\Order;
use app\common\model\report\ReportStatisticByBuyer;
use app\common\service\ChannelAccountConst;
use app\common\service\OrderStatusConst;
use think\Db;
use think\Exception;

/**
 * 统计买家信息
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/1
 * Time: 19:10
 */
class StatisticBuyer
{
    protected $reportStatisticByBuyerModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByBuyerModel)) {
            $this->reportStatisticByBuyerModel = new ReportStatisticByBuyer();
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
        $lists = $this->reportStatisticByBuyerModel->field(true)->where($where)->select();
        return $lists;
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where)
    {
        if (isset($data['sku_id']) && !empty($data['sku_id'])) {
            $where['sku_id'] = ['eq', $data['sku_id']];
        }
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['buyer']) && !empty($data['buyer'])) {
            $where['buyer'] = ['eq', $data['channel_id']];
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
     * @param int $channel_id
     * @param int $begin_time
     * @param int $end_time
     * @param int $sku_id
     */
    public function resetReport($channel_id = 0, $begin_time = 0, $end_time = 0, $sku_id = 0)
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
        $orderList = (new Order())->alias('o')->field('o.channel_id,o.buyer_id,o.create_time,d.sku_id')->join($join)->where($where)->where('o.status', '>', OrderStatusConst::ForDistribution)->select();
        unset($where);
        $orderData = [];
        foreach ($orderList as $k => $value) {
            $time = strtotime(date('Y-m-d', $value['create_time']));
            $key = $value['channel_id'] . '-' . $value['sku_id'] . '-' . $value['buyer_id'] . '-' . $time;
            if (isset($orderData[$key])) {
                $orderData[$key]++;
            } else {
                $orderData[$key] = 1;
            }
        }
        Db::startTrans();
        try {
            $reportModel = new ReportStatisticByBuyer();
            foreach ($orderData as $key => $value) {
                $data = explode('-', $key);
                $where['channel_id'] = ['eq', $data[0]];
                $where['sku_id'] = ['eq', $data[1]];
                $where['buyer'] = ['eq', $data[2]];
                $where['dateline'] = ['eq', $data[3]];
                if (($reportModel->where($where)->count()) == 0) {
                    $insert['channel_id'] = $data[0];
                    $insert['sku_id'] = $data[1];
                    $insert['buyer'] = $data[2];
                    $insert['dateline'] = $data[3];
                    $insert['quantity'] = $value;
                    (new ReportStatisticByBuyer())->allowField(true)->isUpdate(false)->save($insert);
                } else {
                    (new ReportStatisticByBuyer())->where($where)->setInc('quantity', $value);
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }
    }
}