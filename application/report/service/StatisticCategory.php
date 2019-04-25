<?php
namespace app\report\service;

use app\common\model\report\ReportStatisticByCategory;

/** 分类统计
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/1
 * Time: 19:12
 */
class StatisticCategory
{
    protected $reportStatisticByCategoryModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByCategoryModel)) {
            $this->reportStatisticByCategoryModel = new ReportStatisticByCategory();
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
        $lists = $this->reportStatisticByCategoryModel->field(true)->where($where)->select();
        return $lists;
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where)
    {
        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $where['sku_id'] = ['eq', $data['sku_id']];
        }
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
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