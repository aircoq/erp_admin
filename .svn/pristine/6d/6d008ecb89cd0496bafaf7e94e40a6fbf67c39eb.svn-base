<?php
namespace app\report\service;

use app\common\model\report\ReportStatisticByPackage;

/** 包裹统计
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/1
 * Time: 19:18
 */
class StatisticPackage
{
    protected $reportStatisticByPackageModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByPackageModel)) {
            $this->reportStatisticByPackageModel = new ReportStatisticByPackage();
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
        $lists = $this->reportStatisticByPackageModel->field(true)->where($where)->select();
        return $lists;
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where)
    {
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['warehouse_id']) && !empty($data['warehouse_id'])) {
            $where['warehouse_id'] = ['eq', $data['warehouse_id']];
        }
        if (isset($data['warehouse_type']) && !empty($data['warehouse_type'])) {
            $where['warehouse_type'] = ['eq', $data['warehouse_type']];
        }
        if (isset($data['shipping_id']) && !empty($data['shipping_id'])) {
            $where['shipping_id'] = ['eq', $data['shipping_id']];
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
}