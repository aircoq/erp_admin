<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\ExpressConfirmService;
use think\Request;
/**
 * Class ExpressConfirm
 * @package app\report\controller
 * @module 报表系统
 * @title 快递确认单列表
 * @url report/express-confirm
 */
class  ExpressConfirm extends Base
{
    protected $expressConfirmService;

    /**
     * 初始实例化业务逻辑层
     */
    protected function init()
    {
        if(is_null($this->expressConfirmService)){
            $this->expressConfirmService = new expressConfirmService();
        }
    }

    /**
     * @title 快递确认单列表
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\order\controller\Order::channel
     * @apiRelate app\warehouse\controller\Warehouse::info
     * @apiRelate app\warehouse\controller\Carrier::lists
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->expressConfirmService->getExpressForm($page,$pageSize,$params);
        return json($result);
    }


    /**
     * @title execl字段信息
     * @url export-title
     * @method get
     * @return \think\response\Json
     * @apiFilter app\order\filter\OrderByExportFilter
     */
    public function title()
    {

        $exportTitle = $this->expressConfirmService->title();
        $title = [];
        foreach ($exportTitle as $key => $value) {
            if ($value['is_show'] == 1) {
                $temp['key'] = $value['title'];
                $temp['title'] = $value['remark'];
                array_push($title, $temp);
            }
        }
        return json($title);
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
        $ids = $request->post('ids', 0);
        $ids = json_decode($ids, true);

        if (isset($request->header()['x-result-fields'])) {
            $field = $request->header()['x-result-fields'];
            $field = explode(',', $field);
        } else {
            $field = [];
        }

        //判断是否传值
        if ($params['export_type'] == 1 && empty($params['channel_id']) && empty($params['warehouse_id']) && empty($params['carrier_id']) && empty($params['shipping_ids'])){
            //判断时间是否大于七天
            $start = isset($params['date_b']) ? $params['date_b'] : 0;
            $end = isset($params['date_e']) ? $params['date_e'] : 0;
            $days = 0;
            if (!empty($start) && !empty($end)) {
                $start = strtotime($start);
                $end = strtotime($end);
            } else {
                if (!empty($start)) {
                    $start = strtotime($start);
                    $end = time();
                } else {
                    return json(['message' => '最多导出7天的数据'], 400);
                }
            }
            $days = ($end - $start)/86400;
            if ($days > 7 || $days == 0) {
                return json(['message' => '最多导出7天的数据'], 400);
            }
        }
        $result = (new ExpressConfirmService())->exportApply($params, $ids, $field);
        return json($result);
    }
   /**
    * @title 汇总导出
    * @url exports
    * @method post
    */
    public function exports(Request $request)
    {
        $request = Request::instance();
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',20);
        if (isset($request->header()['x-result-fields'])) {
            $field = $request->header()['x-result-fields'];
            $field = explode(',', $field);
        } else {
            $field = [];
        }
        $params = $request->post();
        $result = (new ExpressConfirmService())->exportApplys($page,$pageSize,$params,$field);
        return json($result);
    }
}