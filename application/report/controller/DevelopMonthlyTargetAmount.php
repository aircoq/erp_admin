<?php
namespace app\report\controller;

use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap;
use app\common\service\Common;
use app\common\service\Filter;
use app\common\service\MonthlyModeConst;
use app\report\filter\MonthlyTargetAmountDepartmentFilter;
use app\report\filter\MonthlyTargetAmountFilter;
use think\Request;
use app\report\service\MonthlyTargetAmountService as Server;

/**
 * @module 报表系统
 * @title 月度目标[开发]
 * @url /develop-monthly-target-amount
 */
class DevelopMonthlyTargetAmount extends Base
{
    protected $server;

    protected function init()
    {
        if (is_null($this->server)) {
            $this->server = new Server();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @apiFilter app\report\filter\MonthlyTargetAmountFilter
     * @apiFilter app\report\filter\MonthlyTargetAmountDepartmentFilter
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        return (new MonthlyTargetAmount())->index($request,MonthlyModeConst::mode_development);
    }

    /**
     * @title 首页简报
     * @url all-target
     * @method get
     * @apiFilter app\report\filter\MonthlyTargetAmountFilter
     * @apiFilter app\report\filter\MonthlyTargetAmountDepartmentFilter
     */
    public function getAllDeparment(Request $request)
    {
        return (new MonthlyTargetAmount())->getAllDeparment($request,MonthlyModeConst::mode_development);
    }

    /**
     * @title 下载部门与成员组成表
     * @url export
     * @method post
     */
    public function applyExport(Request $request)
    {
        $params = $request->param();

        $params['mode'] = MonthlyModeConst::mode_development;
        $re = $this->server->applyExport($params);
        return json($re);
    }

    /**
     * @title 下载月度目标报表
     * @url export-monthly
     * @method post
     */
    public function applyExportMonthly(Request $request)
    {

        $params = $request->param();
        $params['mode'] = MonthlyModeConst::mode_development;
        $re = $this->server->applyExportMonthly($params);
        return json($re);
    }

    /**
     * @title 导入成员考核目标
     * @url import
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function import(Request $request)
    {
        $params = $request->param();
        $params['mode'] = MonthlyModeConst::mode_development;
        $result = $this->server->import($params);
        return json(['message' => '操作成功','data' => $result]);
    }

    /**
     * @title 保存导入成员考核目标
     * @url save-import
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function saveImport(Request $request)
    {
        $importData = $request->post('data','');
        $import['year'] = $request->post('year',date('Y'));
        $import['monthly'] = $request->post('monthly',date('m'));
        $import['mode'] = MonthlyModeConst::mode_development;

        if(empty($importData)){
            return json(['message' => '请选择一条记录'],500);
        }
        $importData = json_decode($importData,true);
        $result = $this->server->saveImport($import,$importData);
        return json(['message' => '操作成功','data' => $result]);
    }

    /**
     * @title 重新计算部门人数
     * @url recalculate
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function recalculate(Request $request)
    {
        $import['year'] = $request->post('year',date('Y'));
        $import['monthly'] = $request->post('monthly',date('m'));
        $import['mode'] = MonthlyModeConst::mode_development;

        $result = $this->server->recalculateManAccount('','',$import['mode']);
        return json(['message' => '操作成功','data' => $result]);
    }

}