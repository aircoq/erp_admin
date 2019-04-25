<?php
namespace app\report\controller;

use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap;
use app\common\service\Common;
use app\common\service\MonthlyModeConst;
use think\Request;
use app\report\service\MonthlyTargetAmountService as Server;

/**
 * @module 报表系统
 * @title 月度目标金额[销售]
 * @url /monthly-target-amount
 */
class MonthlyTargetAmount extends Base
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
     * @param int $mode
     * @apiFilter app\report\filter\MonthlyTargetAmountFilter
     * @apiFilter app\report\filter\MonthlyTargetAmountDepartmentFilter
     * @return \think\response\Json
     */
    public function index(Request $request,$mode = MonthlyModeConst::mode_sales)
    {

        $year = $request->get('year',date('Y'));
        $monthly = $request->get('monthly',date('m'));
        $department_id = $request->get('department_id',0);
        $is_pid = $request->get('is_pid',0);
        $types = $request->get('types',0);
//        $result = $this->server->getAllDeparment(1,$year,$monthly,$department_id);

        $user = Common::getUserInfo();
        $userId = $user['user_id'];
        $this->server->permissionsSettings($is_pid, $userId, $department_id);


        $result = $this->server->getAllDeparment(1,$year,$monthly,$department_id,$userId,$mode,$types);

        return json($result);
    }

    /**
     * @title 首页简报
     * @url all-target
     * @method get
     * @apiFilter app\report\filter\MonthlyTargetAmountFilter
     * @apiFilter app\report\filter\MonthlyTargetAmountDepartmentFilter
     */
    public function getAllDeparment(Request $request,$mode = MonthlyModeConst::mode_sales)
    {
        $year = $request->get('year',date('Y'));
        $monthly = $request->get('monthly',date('m'));
        $isInfo = $request->get('info',0);
        $user = Common::getUserInfo();
        $userId = $user['user_id'];
        $department_id = 0;
        $this->server->permissionsSettings(0, $userId, $department_id);


        $result = $this->server->getAllDeparment($isInfo,$year,$monthly,$department_id,$userId,$mode);
        return json($result);
    }

    /**
     * @title 下载部门与成员组成表
     * @url export
     * @method post
     */
    public function applyExport(Request $request)
    {
        $params = $request->param();

        $params['mode'] = MonthlyModeConst::mode_sales;
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
        $params['mode'] = MonthlyModeConst::mode_sales;
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
        $params['mode'] = MonthlyModeConst::mode_sales;
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
        $import['mode'] = MonthlyModeConst::mode_sales;

        if(empty($importData)){
            return json(['message' => '请选择一条记录'],500);
        }
        $importData = json_decode($importData,true);
        $result = $this->server->saveImport($import,$importData);
        return json(['message' => '操作成功','data' => $result]);
    }

    /**
     * @title 重新计算部门人数与平台账号数
     * @url recalculate
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function recalculate(Request $request)
    {
        $import['year'] = $request->post('year',date('Y'));
        $import['monthly'] = $request->post('monthly',date('m'));
        $import['mode'] = MonthlyModeConst::mode_sales;

        $result = $this->server->recalculateManAccount('','',$import['mode']);
        return json(['message' => '操作成功','data' => $result]);
    }

    /**
     * @title 销售额完成进度弹框
     * @url user-ranking
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getUserRanking(Request $request, $mode = MonthlyModeConst::mode_sales)
    {
        $userId = $request->get('user_id',0);

        $result = $this->server->getUserRanking($userId, $mode);
        return json($result);
    }

}