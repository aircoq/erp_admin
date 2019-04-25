<?php


namespace app\report\controller;

use app\common\controller\Base;
use \app\report\service\Invoicing as InvoicingService;
use think\Exception;
use think\Request;

/**
 * Class Invoicing
 * @package app\report\controller
 * @module 报表系统
 * @title 进销存报表
 * @url /invoicing
 */
class Invoicing extends Base
{
    /**
     * @title 汇总报表
     * @method get
     * @url summary
     * @param \think\Request $request
     * @return \think\response\Json
     */
    public function summary(Request $request)
    {
        $params = $request->param();
        $service = new InvoicingService();
        try {
            $service->setStartEndTime($params);
            $service->setWarehouseId($params);
            $service->where($params);
            $page = param($params, 'page', 1);
            $pageSize = param($params, 'pageSize', 20);
            $data = [];
            $count = $service->summaryCount();
            if ($count) {
                $data = $service->summary($page, $pageSize);
            }
            $result = [
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $service->summaryCount(),
                'data' => array_values($data)
            ];
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' =>$ex->getLine().$ex->getMessage()], 400);
        }
    }

    /**
     * @title 明细报表
     * @method get
     * @url detail
     * @param \think\Request $request
     * @return \think\response\Json
     */
    public function detail(Request $request)
    {
        $params = $request->param();
        $service = new InvoicingService();
        try {
            $service->setType('detail');
            $service->setStartEndTime($params);
            $service->setWarehouseId($params);
            $service->where($params);
            $page = param($params, 'page', 1);
            $pageSize = param($params, 'pageSize', 20);
            $data = $service->detail($page, $pageSize);
            $result = [
                'page' => param($params, 'page', 1),
                'pageSize' => param($params, 'pageSize', 20),
                'count' => $service->detailCount(),
                'data' => array_values($data)
            ];
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' =>$ex->getLine().$ex->getMessage()], 400);
        }
    }

    /**
     * @title 明细报表导出
     * @method post
     * @url export/detail
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function exportDetail(Request $request)
    {
        $params = $request->param();
        $service = new InvoicingService();
        try {
            $service->setType('detail');
            $service->applyExport($params);
            return json(['message'=>'申请成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 汇总报表导出
     * @method post
     * @url export/summary
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function exportSummary(Request $request)
    {
        $params = $request->param();
        $service = new InvoicingService();
        try {
            $service->setType('summary');
            $service->applyExport($params);
            return json(['message'=>'申请成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }
}
