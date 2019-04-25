<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\OrderDetailService;
use think\Request;

/**
 * @module 报表系统
 * @title 报表订单详情
 * @url report/order-details
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/11
 * Time: 20:45
 */
class OrderDetail extends Base
{
    protected $orderDetailReport;

    protected function init()
    {
        if (is_null($this->orderDetailReport)) {
            $this->orderDetailReport = new OrderDetailService();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     * @apiFilter app\report\filter\OrderDetailBySellerFilter
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $result = $this->orderDetailReport->lists($page, $pageSize, $params);
        return json($result);
    }

    /**
     * @title 导出
     * @url export
     * @method post
     */
    public function export()
    {
        $request = Request::instance();
        $params = $request->param();
        if (isset($params['date_b']) && isset($params['date_e'])) {
            $startTime = strtotime($params['date_b']);
            $endTime = strtotime($params['date_e']);
            if (($endTime - $startTime) > 7 * 3600 * 24) {
                return json(['message' => '一次最多只能导出7天数据'],400);
            }
        } else {
            return json(['message' => '开始时间与结束时间必须选择'],400);
        }
        $this->orderDetailReport->exportApply($params);
        return json(['message' => '成功加入导出队列']);
    }
}