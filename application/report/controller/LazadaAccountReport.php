<?php


namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\LazadaAccountReportService;
use think\Exception;
use think\Request;



/**
 * Class LazadaAccountReport
 * @package app\report\controller
 * @module 报表系统
 * @title lazada业绩报表
 * @url /report/lazada-account-report
 */
class LazadaAccountReport extends Base
{
    /**
     * @title lazada账号业绩列表
     * @url /report/lazada-account-report/account
     * @param \think\Request $request
     * @return \think\response\Json
     */

    public function account(Request $request)
    {
        $params = $request->param();
        $service = new LazadaAccountReportService();
        try {
            $service->where($params);
            $page = param($params, 'page', 1);
            $pageSize = param($params, 'pageSize', 20);
            $data = $service->accountIndex($page, $pageSize);
            $count = $service->getCount();

            $temp = [
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
                'data' => array_values($data['data']),

            ];
            $result = $data['total'];
            $result = array_merge($result, $temp);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' =>$ex->getLine().$ex->getMessage()], 400);
        }
    }

    /**
     * @title lazada站点列表
     * @url /report/lazada-account-report/site
     * @method get
     * @return \think\response\Json
     */
    public function site(Request $request)
    {
        $params = $request->param();
        $service = new LazadaAccountReportService();
        try {
            $service->where($params);
            $page = param($params, 'page', 1);
            $pageSize = param($params, 'pageSize', 20);
            $data = $service->siteIndex($page, $pageSize);
            $count = $service->siteCount();
            $result = $data['total'];
            $temp = [
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
                'data' => array_values($data['data']),

            ];
            $result = array_merge($result, $temp);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' =>$ex->getLine().$ex->getMessage()], 400);
        }
    }


    /**
     * @title 账号报表导出
     * @method post
     * @url export
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function exportDetail(Request $request)
    {
        $params = $request->param();
        $service = new LazadaAccountReportService();
        try {
            $result = $service->batchExport($params);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }
}
