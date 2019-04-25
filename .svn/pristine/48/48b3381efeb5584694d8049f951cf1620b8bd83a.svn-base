<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/4/18
 * Time: 15:53
 */

namespace app\finance\controller;
use app\common\controller\Base;
use app\finance\service\EbaySettlementService;
use app\finance\service\PaypalTransactionService;
use think\Request;


/**
 * @module 财务管理
 * @title ebay结算报告
 * @url /ebay-settlement
 * @author donghaibo
 * @package app\finance\controller
 */
class EbaySettlement extends Base
{

    /**
     * @title ebay店铺数据统计
     * @method GET
     * @author donghaibo
     */
    public function index(Request $request)
    {
        $param = $request->param();
        $servise = new EbaySettlementService();
        $res = $servise->searchshopStatistics($param);
        $page = isset($param['page'])?intval($param['page']):1;
        $pageSize = isset($param['pageSize'])?intval($param['pageSize']):1;
        if(is_numeric($res))
        {
            switch ($res){
                case 1001:
                    return json(['list'=>[],'count'=>0,'page'=>$page,'pageSize'=>$pageSize],200);
                    break;
                case 1002:
                    return json(['message'=>'时间范围不正确'],400);
                    break;
            }
        }

        return json($res,200);
    }

    /**
     * @title paypal明细
     * @method GET
     * @url /ebay-settlement/paypal-detail
     * @param Request $request
     * @return \think\response\Json
     */
    public function paypal_detail(Request $request)
    {
        $params = $request->param();
        $service = new EbaySettlementService();
        $result = $service->getRowDetail($params);
        return json($result);
    }

    /**
     * @title paypal数据统计
     * @method GET
     * @author donghaibo
     * @url /ebay-settlement/paypal-statistics
     */
    public function pyapalStatistics(Request $request)
    {
        try{
            $params = $request->param();
            $paypalService = new PaypalTransactionService();
            $result = $paypalService->paypalStatistical($params);
            $page = isset($params['page'])?intval($params['page']):1;
            $pageSize = isset($params['pageSize'])?intval($params['pageSize']):1;
            if(is_numeric($result))
            {
                switch ($result)
                {
                    case 1001:
                        return json(['message' => '时间范围不正确'],400);
                        break;
                    case 1002:
                        return json(['list'=>[],'count'=>0,'page'=>$page,'pageSize'=>$pageSize],200);
                        break;
                }

            }
            return json($result);
        }catch (Exception $ex) {
            return json(['message' => $ex->getMessage()],400);
        }
    }

    /**
     * @title 店铺数据导出
     * @method GET
     * @author donghaibo
     * @url /ebay-settlement/shop-statistics-export
     */
    public function exportShopData(Request $request)
    {
        $export_all = $request->param("export_all",0);
        $account_ids = $request->param("account_ids",'');
        if (empty($account_ids) && empty($export_all)) {
            return json(['message' => '请先选择一条记录'], 400);
        }
        $settlementService = new EbaySettlementService();
        $settlementService->exportApply($request->param());
        return json(['message' => '加入导出队列成功']);
    }

    /**
     * @title pyapal数据导出
     * @method GET
     * @author donghaibo
     * @url /ebay-settlement/paypal-statistics-export
     */
    public function exportPaypalData(Request $request)
    {

        $export_all = $request->param("export_all",0);
        $account_ids = $request->param("account_ids",'');
        if (empty($account_ids) && empty($export_all)) {
            return json(['message' => '请先选择一条记录'], 400);
        }
        $paypalService = new PaypalTransactionService();
        $paypalService->exportApply($request->param());
        return json(['message' => '加入导出队列成功']);
    }

}