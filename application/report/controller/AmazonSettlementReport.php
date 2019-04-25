<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/30
 * Time: 15:01
 */

namespace app\report\controller;



use app\common\cache\Cache;
use app\common\controller\Base;
use app\order\service\AmazonGetReport;
use app\order\service\AmazonSettlementReportSummary;
use think\Request;

/**
 * @module 订单系统
 * @title Amazon店铺资金核算
 * @url report/amazon-settlement
 * @author zhuda
 */
class AmazonSettlementReport extends Base
{
    /**
     * @var AmazonSettlementReportSummary
     */
    protected $reportSummary = null;

    public function __construct()
    {
        if (is_null($this->reportSummary)) {
            $this->reportSummary = new AmazonSettlementReportSummary();
        }
    }

    /**
     * @title Amazon结算报告列表
     * @method GET
     * @url summary
     * @return \think\response\Json
     * @apiRelate app\publish\controller\AmazonPublish::getAmazonSite
     * @apiRelate app\publish\controller\AmazonPublish::account
     * @apiRelate app\index\controller\Channel::seller
     * @apiRelate app\report\controller\AmazonSettlementReport::getAccount
     */
    public function summary()
    {
        $result = $this->reportSummary->index();
        return $result;
    }

    /**
     * @title Amazon结算报告列表详情
     * @method GET
     * @url summary-detail
     * @return \think\response\Json
     */
    public function detail()
    {
        $result = $this->reportSummary->detail();
        return $result;
    }

    /**
     * @title  Amazon结算报告导出
     * @method POST
     * @url summary-export
     * @return \think\response\Json
     */
    public function export()
    {
        $result = $this->reportSummary->reportExport();
        return $result;
    }

    /**
     * @title 获取可供选择的导出字段
     * @url export-field
     * @return \think\response\Json
     */
    public function getExportField()
    {
        $result = $this->reportSummary->getBaseField();
        return json($result, 200);
    }


    /**
     * @title 检查结算报告缺失
     * @method GET
     * @url check-report
     * @return \think\response\Json
     */
    public function checkReport()
    {
        $result = $this->reportSummary->checkReportMissing();
        return json($result, 200);
    }

    /**
     * @title 更新report-summary
     * @url update-summary
     * @return \think\response\Json
     */
    public function updateSummary()
    {
        $service = new AmazonGetReport();
        $re = $service->updateReportSummary();
        return json($re, 200);
    }

    /**
     * @title 修复report错误数据
     * @url repair
     * @return \think\response\Json
     */
    public function repair()
    {
        $service = new AmazonGetReport();
        $re = $service->repairReport();
        return json($re, 200);
    }


    /**
     * @title 页面获取账号分页
     * @method get
     * @param Request $request
     * @url account
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function getAccount(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 50);
        $where = [];
        $params = $request->param();

        if (isset($params['site'])) {
            $where[] = ['site', '==', $params['site']];
        }
        $account_list = Cache::store('AmazonAccount')->getAccount();
        if (isset($where)) {
            $account_list = Cache::filter($account_list, $where);
        }
        //总数
        $count = count($account_list);
        $accountData = Cache::page($account_list, $page, $pageSize);
        $new_array = [];
        foreach ($accountData as $k => $v) {
            // $v['updated_time'] = !empty($v['updated_time']) ? date('Y-m-d',$v['updated_time']) : '';
            // $v['is_invalid'] = $v['is_invalid'] == 1 ? true : false;
            $new_array[$k] = $v;
        }
        $new_array = Cache::filter($new_array, [], 'id,code,account_name');
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];

        return json($result, 200);
    }

}