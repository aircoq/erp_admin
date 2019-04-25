<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\AreaSalesAnalysis as AreaSalesAnalysisService;
use think\Request;

/**
 * @module 报表系统
 * @title 区域销量分析
 * @url report/area-sales-analysis
 * Created by PhpStorm.
 * User: lanshushu
 * Date: 2019/03/27
 * Time: 09:58
 */
class AreaSalesAnalysis extends Base
{
    protected $areaSalesAnalysisService;
    protected function init()
    {
        if(is_null($this->areaSalesAnalysisService)){
            $this->areaSalesAnalysisService = new AreaSalesAnalysisService();
        }
    }

    /**
     * @title 区域销量分析列表
     * @param Request $request
     * @return \think\response\Json
     * @apiFilter app\report\filter\AreaSalesAnalysisBySellerFilter
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->areaSalesAnalysisService->lists($page, $pageSize, $params);
        return json($result);
    }
    /**
     * @title 区域销量分析批量导出
     * @url export
     * @method post
     * @return \think\response\Json
     * @apiFilter app\report\filter\AreaSalesAnalysisBySellerFilter
     */
    public function export()
    {
        $request = Request::instance();
        $params = $request->param();
        $result = $this->areaSalesAnalysisService->exportOnLine($params);
        return json($result);
    }

}